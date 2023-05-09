<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\Request;

class FilterFormer
{
    public function formFilter(Request $request): array
    {
        $filter = array();
        if ($request->query->get("type", null) !== null) {
            $filter['type'] = $request->query->get("type", null);
        }
        if ($request->query->get("course_code", null) !== null) {
            $filter['course_code'] = $request->query->get("course_code", null);
        }
        if ($request->query->get("skip_expired", null) !== null) {
            $filter['skip_expired'] = $request->query->get("skip_expired", null);
        }
        return $filter;
    }

}