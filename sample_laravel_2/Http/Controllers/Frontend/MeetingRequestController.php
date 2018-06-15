<?php

namespace App\Http\Controllers\Frontend;


use App\Conference;
use App\Delegate;
use App\Http\Controllers\Controller;
use App\Mail\SendMeetingRequestNotification;
use App\MeetingRequest;
use App\MeetingTimeslot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class MeetingRequestController extends Controller
{

    private $conference;

    /**
     * ProfileController constructor.
     *
     * @param  Request $request
     */
    public function __construct(Request $request) {
        $this->middleware('delegate.auth');

        $currentConference = $request->segment(1);
        $this->conference = Conference::where('url_slug', $currentConference)->first();
    }

    /**
     * Retrieve delegates request history
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAll() {
        $delegate = Auth::guard('delegate')->user();

        $requests = MeetingRequest::with([
            'delegate',
            'delegate.photo',
            'meetingTimeslot',
            'meetingTimeslot.day',
        ])->where('target_id', $delegate->id)
            ->where('status', 'awaiting')->take(5)->get();

        return response()->json($requests);
    }

    /**
     * Create Meeting Request with other delegate
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request) {
        $data = $request->all();
        Validator::make($data, [
            'target'   => 'required',
            'timeslot' => 'required'
        ])->validate();

        $delegate = Auth::guard('delegate')->user();
        $meetingTimeslot = MeetingTimeslot::find($data['timeslot']);
        $target = Delegate::find($data['target']);

        if (!$meetingTimeslot || !$target) {
            logger('MeetingRequestController@create::: ', [$data]);
            return response()->json([
                'message' => 'Something went wrong'
            ], 403);
        }
        $checkIfAvailableSlot = $delegate->meetingRequests()
            ->where('meeting_timeslot_id', $meetingTimeslot->id)->first();
        if ($checkIfAvailableSlot) {
            return response()->json([
                'timeslot' => ['You already send request to this time slot']
            ], 403);
        }

        $meetingRequest = new MeetingRequest();
        $meetingRequest->notice = $data['note'] ?? '';
        $meetingRequest->delegate()->associate($delegate);
        $meetingRequest->target()->associate($target);
        $meetingRequest->meetingTimeslot()->associate($meetingTimeslot);
        $meetingRequest->save();

        \Mail::to($target->email)
            ->cc($this->conference->admin_email)
            ->bcc($this->conference->bbc_email ?? '')
            ->send(new SendMeetingRequestNotification($delegate, 'delegate', $target, $data['note'] ?? ''));

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Cancel request
     *
     * @param Conference $conference
     * @param MeetingRequest $meeting_request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Conference $conference, MeetingRequest $meeting_request) {
        if ($meeting_request) {
            $meeting_request->status = 'declined';
            $meeting_request->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 403);
    }

    /**
     * Accept request
     *
     * @param Conference $conference
     * @param MeetingRequest $meeting_request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function accept(Conference $conference, MeetingRequest $meeting_request) {
        if ($meeting_request) {
            $meeting_request->status = 'confirmed';
            $meeting_request->save();

            $meeting_request->meetingTimeslot->meetingRequests()->where('id', '!=', $meeting_request->id)->delete();

            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 403);
    }

}