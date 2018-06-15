<?php

namespace App\Helpers;

use App\Corporate;
use App\Delegate;
use App\RegformField;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;

class General
{

    public static function getConference() {
        return Request::segment(1);
    }

    public static function conferenceSlug($routeName) {
        $route = self::conferenceRoute($routeName);
        $routeChunks = explode('/', $route);

        return $routeChunks[count($routeChunks) - 1];
    }

    public static function conferenceRoute($routeName, $param = false) {
        if ($param) {
            return route($routeName, [Request::segment(1), $param]);
        }

        return route($routeName, Request::segment(1));
    }

    public static function countdownDateFromString($string) {
        $d = date_parse($string);

        return $d['day'] . ' ' . \DateTime::createFromFormat('!m', $d['month'])->format('F') . ' ' . $d['year'];
    }

    public static function CollectionToSelect2($collection) {
        $select = [];
        foreach ($collection as $c) {
            $select[] = ['text' => $c->name, 'id' => $c->id];
        };

        return json_encode($select);
    }

    public static function ImageCollectionToSelect2($collection) {
        $select = [];
        foreach ($collection as $c) {
            $select[] = ['text' => $c->filename, 'id' => $c->id, 'path' => $c->path, 'size' => $c->size, 'width' => $c->width, 'height' => $c->height];
        };

        return json_encode($select);
    }

    public static function CollectionToTable($collection) {
        if (!$collection) {
            return "[]";
        }
        $table = [];
        foreach ($collection as $c) {
            $table[] = $c;
        }

        return json_encode($table);
    }

    public static function AvailableThemes() {
        $themes = File::directories(resource_path('views') . '/frontend/themes');

        $select = [];
        foreach ($themes as $c) {
            $t = array_last(explode('/', $c));
            $select[] = ['text' => $t, 'id' => $t];
        };

        return json_encode($select);
    }

    public static function adjustBrightness($hex, $steps) {
        // Steps should be between -255 and 255. Negative = darker, positive = lighter
        $steps = max(-255, min(255, $steps));

        // Normalize into a six character long hex string
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
        }

        // Split into three parts: R, G and B
        $color_parts = str_split($hex, 2);
        $return = '#';

        foreach ($color_parts as $color) {
            $color = hexdec($color); // Convert to decimal
            $color = max(0, min(255, $color + $steps)); // Adjust color
            $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
        }

        return $return;
    }

    /**
     * @param $data
     * @param $by
     *
     * @return array
     */
    public static function orderDataBy($data, $by) {
        usort($data, function ($a, $b) use ($by) {
            if ($a[$by] == $b[$by]) {
                return 0;
            }
            return $a[$by] < $b[$by] ? -1 : 1;
        });
        return $data;
    }

    public static function filterEmailContent(Delegate $delegate, $content) {

        $shortcodes = RegformField::select(['id', 'name', 'label'])->whereNotIn('type', ['start', 'end', 'header', 'subheader'])->where('conference_id', Auth::user()->getActiveConferenceId())->get();

        foreach ($shortcodes as $shortcode) {
            if (!isset($delegate->meta[$shortcode->name])) {
                if (isset($delegate[$shortcode->name])) {
                    $content = str_replace('[' . $shortcode->name . ']', $delegate[$shortcode->name], $content);
                }
            } else {
                $content = str_replace('[' . $shortcode->name . ']', $delegate->meta[$shortcode->name], $content);
            }

        }

        $content = str_replace('[UIC]', $delegate->uic, $content);

        return $content;
    }

    public static function getDelegateAgenda(Delegate $delegate) {

        $conference = Auth::user()->conference;
        $unavailableList = $delegate->unavailableTimeslots->groupBy('type')->toArray();

        foreach ($unavailableList as $key => $item) {
            $unavailableList[$key] = array_map(function ($timeslot) {
                return $timeslot['timeslot_id'];
            }, $item);
        }

        $schedule = $conference->days()->with([
            'scheduleTimeslots'                  => function ($q) use ($unavailableList) {
                if (isset($unavailableList['programs'])) {
                    $q->whereNotIn('id', $unavailableList['programs']);
                }
            },
            'scheduleTimeslots.speakers',
            'meetingTimeslots.corporateMeetings' => function ($q) use ($delegate) {
                $q->where('status', '=', 'set')->where(function ($query) use ($delegate) {
                    $query->where('delegate_id', $delegate->id);
                });
            },
            'meetingTimeslots.corporateMeetings.corporate',
            'meetingTimeslots',
            'meetingTimeslots.meetingRequests'   => function ($q) use ($delegate) {
                $q->where('status', '!=', 'declined')->where(function ($query) use ($delegate) {
                    $query->where('delegate_id', $delegate->id)->orWhere('target_id', $delegate->id);
                });
            },
            'meetingTimeslots.meetingRequests.target',
            'meetingTimeslots.meetingRequests.target.photo',
            'meetingTimeslots.meetingRequests.delegate',
            'meetingTimeslots.meetingRequests.delegate.photo',
            'personalAppointments'               => function ($q) use ($delegate) {
                $q->where('delegate_id', $delegate->id);
            }
        ])->get();

        $agenda = $schedule->toArray();

        foreach ($schedule as $index => $day) {

            if ($day['meetingTimeslots']) {
                foreach ($day['meetingTimeslots'] as $i => $meeting) {
                    $meeting['type'] = 'meeting_timeslots';

                    array_push($agenda[$index]['schedule_timeslots'], $meeting->toArray());
                }
            }

            if ($day['personalAppointments']) {
                foreach ($day['personalAppointments'] as $i => $appointment) {
                    $appointment['type'] = 'personal_appointments';
                    array_push($agenda[$index]['schedule_timeslots'], $appointment->toArray());
                }
            }
        }

        foreach ($schedule as $index => $day) {

            usort($agenda[$index]['schedule_timeslots'], function ($a, $b) {

                $calc = floatVal(str_replace(':', '.', $a['time_from'])) - floatVal(str_replace(':', '.', $b['time_from']));
                if ($calc == 0) {
                    $calc = floatVal(str_replace(':', '.', $b['time_to'])) - floatVal(str_replace(':', '.', $a['time_to']));
                }

                return $calc;
            });
        }

        return $agenda;
    }

    public static function getCorporateAgenda(Corporate $corporate) {

        $conference = Auth::user()->conference;

        $schedule = $conference->days()->with([
            'scheduleTimeslots',
            'meetingTimeslots.corporateMeetings'          => function ($q) use ($corporate) {
                $q->where('status', '=', 'set')->where(function ($query) use ($corporate) {
                    $query->where('corporate_id', $corporate->id);
                });
            },
            'meetingTimeslots.corporateMeetings.investor' => function ($q) use ($corporate) {
                $q->orderBy('organisation', 'DESC');
            },
            'meetingTimeslots'
        ])->get();


        return $schedule;
    }

}
