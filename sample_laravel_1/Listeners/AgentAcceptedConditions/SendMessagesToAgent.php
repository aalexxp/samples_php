<?php

namespace App\Listeners\AgentAcceptedConditions;

use App\Events\AgentAcceptedConditions;
use App\Models\Comms\Template;
use App\Models\Contact;
use App\Models\Messages\MsgEmail;
use App\Models\Messages\MsgSms;
use App\Models\Property;
use App\Services\ContactService;
use App\Services\Interfaces\SmsInterface;
use App\Services\ParametersService;
use App\Services\TemplateTagsService;
use Exception;
use Illuminate\Mail\Message;

class SendMessagesToAgent
{

    protected $parameters = null;
    protected $tts = null;
    protected $contactService = null;
    protected $smsService = null;

    public function __construct(
        ParametersService $parameters,
        TemplateTagsService $tts,
        ContactService $contactService,
        SmsInterface $smsService
    ) {
        $this->parameters = $parameters;
        $this->tts = $tts;
        $this->contactService = $contactService;
        $this->smsService = $smsService;
    }

    public function handle(AgentAcceptedConditions $event)
    {
        if ($agentTemplate = $this->parameters->get(ParametersService::PARAM_AGENT_AUTO_MESSAGE_TEMPLATE)) {
            $property = $event->getProperty();
            $property->load(['owner', 'staff', 'staff.signature']);
            // Let's try to load the template
            /** @var Template $template */
            $template = Template::findOrNew($agentTemplate);
            if ($template->id) {
                $this->tts->defaultProperty($property);
                $this->tts->defaultSignature($property->staff->signature);
                $this->tts->defaultTemplate($template);

                $agentNumber = $this->tts->getAgentNumber($event->getAgent());
                if ($agentNumber) {
                    $this->tts->convertAgentTagsToAgent($agentNumber);
                    $this->sendEmailToAgent($property, $template, $event->getAgent());
                    $this->sendSmsToAgent($property, $template, $event->getAgent());
                } else {
                    \Log::error("Agent Number wasn't find for the contact",
                        ['property' => $property->id, 'agent' => $event->getAgent()->id]);
                }
            }
        }
    }

    protected function sendEmailToAgent(Property $property, Template $template, Contact $agent)
    {
        if ($property->owner AND $to = $this->contactService->getPreferredEmail($agent)) {
            try {
                $data = [
                    'body' => $this->tts->parse($template->body),
                    'subject' => $this->tts->parse($template->subject)
                ];
                $from = $this->tts->getReplyToEmail(null, null, $agent);
                \Mail::send('simple_body', $data,
                    function (Message $message) use ($data, $template, $property, $from, $to, $agent) {
                        $message
                            ->to($to, $agent->first_name . ' ' . $agent->last_name)
                            ->replyTo($from, $property->staff->name)
                            ->from($from, $property->staff->name)
                            ->subject($data['subject'])
                            ->embedData((env('MAIL_DRIVER') === 'sendgrid' ? [
                                'categories' => [
                                    $template->name,
                                    'crm_message'
                                ]
                            ] : 'crm_message'), 'sendgrid/x-smtpapi');
                    });

                // Mail was sent... let's store this email in our database
                $email = new MsgEmail([
                    'template_id' => $template->id,
                    'signature_id' => $this->tts->getDefaultSignature() ? $this->tts->getDefaultSignature()->id : null,
                    'property_id' => $property->id,
                    'from' => $from
                ]);

                $email->contact_id = $agent->id;

                $email->fill($data);
                $email->to = $to;
                $email->save();

            } catch (Exception $e) {
                \Log::error("SendMessageToAgent error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }
        }

    }

    protected function sendSmsToAgent(Property $property, Template $template, Contact $agent)
    {
        $contact = $agent->id;

        if ($contact) {
            $phone = $this->contactService->getPreferredPhone($agent);

            if (mb_strlen($phone) > 3) {
                // Let's send message
                $sms = new MsgSms([
                    'template_id' => $template->id,
                    'property_id' => $property->id,
                    'signature_id' => $this->tts->getDefaultSignature(true),
                    'from' => $this->smsService->getDefaultFromNumber(),
                ]);

                $sms->to = $phone;

                $sms->status = MsgSms::STATUS_CREATED;
                $sms->contact_id = $contact;
                $sms->body = $this->tts->parseSms($template->sms_text);

                if (empty($sms->body)) {
                    \Log::warning('SMS with empty body wasn\'t sent', ['contact' => $contact, 'phone' => $phone]);

                    return;
                }

                if (empty($sms->to)) {
                    return;
                }

                $sms->save();
                $this->smsService->send($sms);
            } else {
                \Log::error("SMS to Agent wasn't sent because of incorrect phone",
                    ['property' => $property->id, 'contact' => $contact, 'number' => $phone]);
            }
        } else {
            \Log::error("SMS to Agent wasn't sent because of incorrect Agent ID",
                ['property' => $property->id]);
        }
    }
}