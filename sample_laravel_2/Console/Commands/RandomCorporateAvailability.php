<?php

namespace App\Console\Commands;

use App\Corporate;
use App\MeetingTimeslot;
use Illuminate\Console\Command;

class RandomCorporateAvailability extends Command
{
    public $conferences;
    public $timeslots;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fake:random_corporate_availability';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        $this->corporates = Corporate::all('id');
        $this->timeslots = MeetingTimeslot::all('id');
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {

        $type = $this->choice('Should we make corporates randomly available or always available?', ['random', 'all'], 0);

        foreach ($this->corporates as $k => $corporate) {
            $corporate->meetingTimeslots()->sync([]);

            $prepared = [];
            foreach ($this->timeslots as $timeslot) {
                $prepared[$timeslot->id] = ['corporate_id' => $corporate->id];
            }

            $corporate->meetingTimeslots()->sync($prepared);

        }

        $this->info("Finished setting corporate availability");
    }
}
