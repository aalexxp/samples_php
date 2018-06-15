<?php namespace App\Http\Controllers\Admin\CSV;

use App\Corporate;
use App\Delegate;
use App\Helpers\CSVExporter;
use App\Http\Controllers\Admin\BaseController;
use Illuminate\Support\Facades\Auth;

/**
 * Class HomeController
 * @package App\Http\Controllers
 */
class MatrixExportController extends BaseController
{

    use CSVExporter;

    /**
     * Export all corporates data as CSV
     * @return bool|mixed
     */
    public function exportCorporates() {
        $self = Auth::user();

        $conference = $self->conference;
        $days = $conference->days()->with('meetingTimeslots')->get();

        $tpl = $this->getColumnTemplate($days);
        $finalList = [];


        $corporates = Corporate::with([
            'meetingTimeslots',
            'meetingTimeslots.day',
            'meetingTimeslots.corporateRequests',
            'meetingTimeslots.corporateRequests.delegate'
        ])
            ->where('conference_id', $conference->id)->get();

        foreach ($corporates as $corporate) {
            $nextCorp = [];
            $nextCorp['Corporate'] = $corporate->title;
            $nextCorp += $tpl;
            if (!$corporate->meetingTimeslots) {
                continue;
            }
            foreach ($corporate->meetingTimeslots as $meeting_timeslot) {
                $timeSlotName = $meeting_timeslot->day->name . ' | ' . $meeting_timeslot->title . ' (' . $meeting_timeslot->time_from . '-' . $meeting_timeslot->time_to . ')';
                $requests = $meeting_timeslot->corporateRequests()->with('delegate')->where('corporate_id', $corporate->id)->get()->toArray();
                $investors = implode(', ', array_map(function ($request) {
                    return $request['delegate']['first_name'] . ' ' . $request['delegate']['last_name'];
                }, $requests));
                $nextCorp[$timeSlotName] = $investors;
            }
            $finalList[] = $nextCorp;
        }

        return $this->exportCSV($finalList, 'corporates');
    }

    /**
     * Generate all time slots as array keys
     *
     * @param $days
     *
     * @return array
     */
    protected function getColumnTemplate($days) {
        $tpl = [];
        foreach ($days as $day) {
            foreach ($day->meetingTimeslots as $meeting_timeslot) {
                $timeSlotName = $day->name . ' | ' . $meeting_timeslot->title . ' (' . $meeting_timeslot->time_from . '-' . $meeting_timeslot->time_to . ')';
                $tpl[$timeSlotName] = '';
            }
        }

        return $tpl;
    }

    /**
     * Export all investors data as CSV
     * @return bool|mixed
     */
    public function exportInvestors() {
        $self = Auth::user();

        $conference = $self->conference;
        $days = $conference->days()->with('meetingTimeslots')->get();

        $tpl = $this->getColumnTemplate($days);
        $finalList = [];


        $investors = Delegate::with([
            'corporateRequests' => function ($query) {
                $query->where('status', 'confirmed');
            },
            'corporateRequests.corporate',
            'corporateRequests.meetingTimeslot',
            'corporateRequests.meetingTimeslot.day',
        ])
            ->where('conference_id', $conference->id)
            ->whereNotNull('rank')
            ->where('rank', '!=', 'delegate')
            ->get();


        foreach ($investors as $investor) {
            $nextInvestor = [];
            $nextInvestor['Investor'] = $investor->first_name . ' ' . $investor->last_name;
            $nextInvestor += $tpl;
            if (!$investor->corporateRequests) {
                continue;
            }
            foreach ($investor->corporateRequests as $corporate_request) {
                $meeting_timeslot = $corporate_request->meetingTimeslot;
                $timeSlotName = $meeting_timeslot->day->name . ' | ' . $meeting_timeslot->title . ' (' . $meeting_timeslot->time_from . '-' . $meeting_timeslot->time_to . ')';
                $investors = implode(', ', array_map(function ($request) {
                    return $request['corporate']['title'];
                }, $corporate_request));
                $nextInvestor[$timeSlotName] = $investors;
            }
            $finalList[] = $nextInvestor;
        }

        return $this->exportCSV($finalList, 'investors');
    }

}
