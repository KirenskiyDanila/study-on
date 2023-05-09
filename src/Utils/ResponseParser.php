<?php

namespace App\Utils;

class ResponseParser
{
    public function parseTransactions(array $transactionResponse): array
    {
        $transactions = array();
        foreach ($transactionResponse as $item) {
            if (isset($item['expires_at'])) {
                $transactions[$item['course_code']]['type'] = 'rent';
                $transactions[$item['course_code']]['expires_at'] = $item['expires_at'];
            } else {
                $transactions[$item['course_code']]['type'] = 'buy';
            }
        }
        return $transactions;
    }

    public function parseCourses(array $courseResponse, array $courses): array
    {
        $coursesArray = array();
        foreach ($courses as $course) {
            $courseElement = array();
            $courseElement['content'] = $course;
            foreach ($courseResponse as $item) {
                if ($item['code'] === $course->getCode()) {
                    $courseElement['type'] = $item['type'];
                    if (isset($item['price'])) {
                        $courseElement['price'] = $item['price'];
                    }
                    break;
                }
            }
            if (!isset($courseElement['type'])) {
                $courseElement['type'] = 'free';
            }
            $coursesArray[] = $courseElement;
        }
        return $coursesArray;
    }

}