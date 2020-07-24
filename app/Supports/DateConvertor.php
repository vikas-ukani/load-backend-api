<?php

namespace App\Supports;

use Illuminate\Support\Carbon;

/**
 * 
 */
trait DateConvertor
{
    /**            
     * isoToUTCFormat => convert ISOString to UTC date format
     *  
     ** 2019-10-09T18:30:00.000Z => 2019-10-09 18:30:00.0 UTC (+00:00) 
     * 
     * @param  mixed $date => ISO String Date Format 
     *
     * @return void
     */
    protected function isoToUTCFormat($date)
    {

        $gameStart = Carbon::parse($date, env('APP_TIMEZONE'));  // , 'UTC'
        $gameStart = new Carbon($gameStart->toDateTimeString());
        // $gameStart->timezone = ;
        return $gameStart;

        // $timestamp = '2014-02-06 16:34:00';
        // $date = Carbon::createFromFormat('Y-m-d H:i:s', $timestamp, 'Europe/Stockholm');
        // $date->setTimezone('UTC');
    }

    protected function utcToDateTimeFormat($date)
    {
        $date = Carbon::createFromFormat(
            'Y-m-d\TH:i:s\Z',
            $date,
            env('APP_TIMEZONE')
        );
        $date = $date->format('Y-m-d H:i:s');
        return $date;
    }

    protected function getCurrentYear()
    {
        return (int) \Carbon\Carbon::now()->year;
    }



    /**
     * getCurrentDateUTC => get Current date in UTC format
     *
     * @return void
     */
    protected function getCurrentDateUTC()
    {
        $date = \Carbon\Carbon::now();
        $date = $date->toDateTimeString();
        return $date;
    }

    protected function getCurrentMonthByGivenDate($date)
    {
        // $date = \Carbon\Carbon::now();
        $date = $date->toDateTimeString();
        // dd('check month', $date);
        return $date;
    }

    /**
     * subtractWeeksFromCurrentUTC => For Subtract week from current UTC date
     *
     * @param  mixed $numberOfWeeks
     *
     * @return void
     */
    protected function subtractWeeksFromCurrentUTC($numberOfWeeks = 0)
    {
        $currentDate = \Carbon\Carbon::now();
        $weekAgoDate = $currentDate->subWeeks($numberOfWeeks)->toDateTimeString();
        return $weekAgoDate;
    }


    protected function getCurrentStartOfTheDay()
    {
        $now = \Carbon\Carbon::now();
        $weekStartDate = $now->startOfDay()->toDateTimeString();
        return $weekStartDate;
    }

    protected function getCurrentEndOfTheDay()
    {
        $now = \Carbon\Carbon::now();
        $weekEndDate = $now->endOfDay()->toDateTimeString();
        return $weekEndDate;
    }


    protected function getDateWiseStartOfTheWeek($date)
    {
        $weekStartDate = $date->startOfWeek()->toDateTimeString();
        return $weekStartDate;
    }
    // getDateWiseEndOfTheWeek
    protected function getDateWiseEndOfTheWeek($date)
    {
        $weekEndDate = $date->endOfWeek()->toDateTimeString();
        return $weekEndDate;
    }

    protected function getCurrentStartOfTheWeek()
    {
        $now = \Carbon\Carbon::now();
        $weekStartDate = $now->startOfWeek()->toDateTimeString();
        return $weekStartDate;
    }

    protected function getCurrentEndOfTheWeek()
    {
        $now = \Carbon\Carbon::now();
        $weekEndDate = $now->endOfWeek()->toDateTimeString();
        return $weekEndDate;
    }

    protected function getDateWisePreviousStartOfTheWeek($date)
    {
        $previousWeekStartDate = $date->startOfWeek()->subWeek(1)->toDateTimeString();
        return $previousWeekStartDate;
    }
    protected function getDateWisePreviousEndOfTheWeek($date)
    {
        $previousWeekStartDate = $date->endOfWeek()->subWeek(1)->toDateTimeString();
        return $previousWeekStartDate;
    }

    protected function getPreviousStartOfTheWeek()
    {
        $now = \Carbon\Carbon::now();
        $previousWeekStartDate = $now->startOfWeek()->subWeek(1)->toDateTimeString();
        return $previousWeekStartDate;
    }
    protected function getPreviousEndOfTheWeek()
    {
        $now = \Carbon\Carbon::now();
        $previousWeekStartDate = $now->endOfWeek()->subWeek(1)->toDateTimeString();
        return $previousWeekStartDate;
    }

    public function getAllWeeksDatesFromDateRange($startDate, $endDate, $format = 'Y-m-d')
    {
        $dateRangeArray = \Carbon\CarbonPeriod::create($startDate, $endDate)->toArray();
        foreach ($dateRangeArray as $carbonObj) {
            $allDates[] = $carbonObj->format($format);
        }
        $allDates = array_chunk($allDates, 7);
        return $allDates;
    }

    public function getLast30DayDate()
    {
        $date = \Carbon\Carbon::today()->subDays(30);
        $date = $date->toDateTimeString();
        return $date;
    }

    /**
     * getAgeFromDateOfBirth => age from date 
     *
     * @param  mixed $date_of_birth => 02-01-1996
     * @return int
     */
    public function getAgeFromDateOfBirth($date_of_birth)
    {
        $ageYearDate = Carbon::parse(
            $date_of_birth,
            env('APP_TIMEZONE')
        );  // , 'UTC'
        $ageYear = $ageYearDate->year;
        $currentYear = $this->getCurrentYear();

        return $currentYear - $ageYear;
        // dd('current year of ', $currentYear, $ageYear, $date_of_birth, $currentYear - $ageYear);
        // return 0; 
    }
}
