<?php


namespace App\Http\Controllers\Frontend;


use App\Conference;
use App\Http\Controllers\Controller;
use App\PersonalAppointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class AppointmentController extends Controller
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
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request) {
        $delegate = Auth::guard('delegate')->user();

        $data = $request->all();
        $messages = [
            'time_from.required'    => 'Start Time field is required',
            'time_from.H.required'  => 'Start Time hours is required',
            'time_from.mm.required' => 'Start Time minutes is required',
            'time_to.required'      => 'End Time field is required',
            'time_to.H.required'    => 'End Time hours is required',
            'time_to.mm.required'   => 'End Time minutes is required',
        ];
        Validator::make($data, [
            'title'        => 'required',
            'day'          => 'required',
            'time_from'    => 'required',
            'time_from.H'  => 'required',
            'time_from.mm' => 'required',
            'time_to'      => 'required',
            'time_to.H'    => 'required',
            'time_to.mm'   => 'required',
        ], $messages)->validate();

        foreach ($data['day'] as $day) {
            $delegate->personalAppointments()->create([
                'day_id'    => $day,
                'title'     => $data['title'],
                'comment'   => $data['comment'] ?? '',
                'time_from' => $data['time_from']['H'] . '.' . $data['time_from']['mm'],
                'time_to'   => $data['time_to']['H'] . '.' . $data['time_to']['mm'],
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Delete Personal Appointment
     *
     * @param Conference $conference
     * @param PersonalAppointment $personal_appointment
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(Conference $conference, PersonalAppointment $personal_appointment) {
        $success = false;

        if ($personal_appointment) {
            $success = $personal_appointment->delete();
        }

        return response()->json(['success' => $success]);
    }

}