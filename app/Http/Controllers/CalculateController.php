<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use Illuminate\Http\Request;

class CalculateController extends Controller
{
    public $mrp = 3180;
    public $mzp = 60000;

    /**
     * Show result.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function show(Request $request)
    {
        $formData = json_decode(html_entity_decode($request->query('formData')), true);
        $data = array_map('trim', $formData);

        [$response, $isValidated] = $this->validateForm($data);

        if (!$isValidated) {
            return response()->json($response, 400);
        }

        $result = $this->handle($data);

        return response()->json($result, 200);
    }

    /**
     * Handle request.
     *
     * @param array $input
     * @return array|\Illuminate\Http\JsonResponse
     *
     */
    public function handle($data)
    {
        $result = [];
        $result["taxes"] = $this->calculateTaxes($data);
        $result["salary"] = $this->calculateSalaryWithTax($data, $result["taxes"]);

        return $result;
    }

    /**
     * Validate Form data.
     *
     * @param array
     * @return array
     *
     */
    public function validateForm($data)
    {
        if (empty($data["salary"])) {
            return ['Поле "Оклад" не введено', false];
        }
        if (!is_numeric($data["salary"])) {
            return ['Поле "Оклад" содержит некорректные данные', false];
        }

        if (empty($data["daysCount"])) {
            return ['Поле "Норма дней в месяце" не введено', false];
        }
        if (!is_numeric($data["daysCount"])) {
            return ['Поле "Норма дней в месяце" содержит некорректные данные', false];
        }
        if ($data["daysCount"] < 0 || $data["daysCount"] > 31) {
            return ['Поле "Норма дней в месяце" не может быть меньше 0 или больше 31', false];
        }

        if (empty($data["workDays"])) {
            return ['Поле "Отработанное количество дней" не введено', false];
        }
        if (!is_numeric($data["workDays"])) {
            return ['Поле "Отработанное количество дней" содержит некорректные данные', false];
        }
        if ($data["workDays"] < 0 || $data["workDays"] > 31) {
            return ['Поле "Отработанное количество дней" не может быть меньше 0 или больше 32', false];
        }

        if (empty($data["calendarYear"])) {
            return ['Поле "Календарьный год" не введено', false];
        }
        if (!is_numeric($data["calendarYear"])) {
            return ['Поле "Календарьный год" содержит некорректные данные', false];
        }
        if ($data["calendarYear"] < 1901 || $data["calendarYear"] > 2155) {
            return ['Поле "Отработанное количество дней" не может быть меньше 1901 или больше 2155', false];
        }

        if (empty($data["calendarMonth"])) {
            return ['Поле "Календарьный месяц" не введено', false];
        }
        if (!is_numeric($data["calendarMonth"])) {
            return ['Поле "Календарьный месяц" содержит некорректные данные', false];
        }
        if ($data["calendarMonth"] < 0 || $data["calendarMonth"] > 12) {
            return ['Поле "Календарьный месяц" не может быть меньше 0 или больше 12', false];
        }

        if ($data["isInvalid"]) {
            if (empty($data["invalidDegree"])) {
                return ['Поле "Группа инвалидности" не введено', false];
            }
            if (!is_numeric($data["invalidDegree"])) {
                return ['Поле "Группа инвалидности" содержит некорректные данные', false];
            }
            if ($data["invalidDegree"] < 0 || $data["invalidDegree"] > 4) {
                return ['Поле "Группа инвалидности" не может быть меньше 0 или больше 4', false];
            }
        }

        return ["Все поля корректны", true];
    }

    /**
     * Calculate salary.
     *
     * @param array
     * @return float
     *
     */
    public function calculateSalary($data) {
        return round(($data["salary"] * $data["workDays"]) / $data["daysCount"], 2);
    }

    /**
     * Calculate taxes.
     *
     * @param array
     * @return array
     *
     */
    public function calculateTaxes($data) {
        $salary = $this->calculateSalary($data);

        $ipn = 0;
        $opv = 0;
        $osms = 0;
        $vosms = 0;
        $so = 0;

        if (!($data["isPensioner"] && $data["isInvalid"])) {
            $opv = round($salary * 0.1, 2);
            $vosms = round($salary * 0.02, 2);
            $osms = round($salary * 0.02, 2);
            $mzp = $data["hasTax"] ? $this->mzp : 0;

            $correction = 0;
            if ((25 * $this->mrp) > $salary) {
                $correction = round(($salary - $opv - $mzp - $vosms) * 0.9, 2);
            }

            $ipn = round(($salary - $opv - $mzp - $vosms - $correction) * 0.1, 2);

            $so = round(($salary - $opv) * 0.035, 2);

            if ($data["isPensioner"]) {
                $opv = 0;
                $osms = 0;
                $vosms = 0;
                $so = 0;
            }

            if ($data["isInvalid"]) {
                switch ($data["invalidDegree"]) {
                    case 1:
                    case 2:
                        $opv = 0;
                        break;
                    case 3:
                        break;
                }

                $ipn = $salary > (882 * $this->mrp) ? $ipn : 0;
                $osms = 0;
                $vosms = 0;
            }
        }

        $array = [];
        $array["ipn"] = ["name" => "Индивидуальный подоходный налог (ИПН)", "value" => $ipn];
        $array["opv"] = ["name" => "Обязательные пенсионные взносы (ОПВ)", "value" => $opv];
        $array["osms"] = ["name" => "Обязательное социальное медицинское страхование (ОСМС)", "value" => $osms];
        $array["vosms"] = ["name" => "Взносы на обязательное социальное медицинское Страхование (ВОСМС)", "value" => $vosms];
        $array["so"] = ["name" => "Социальные отчисления (СО)", "value" => $so];

        return $array;
    }

    /**
     * Calculate salary.
     *
     * @param array
     * @return float
     *
     */
    public function calculateSalaryWithTax($data, $taxes) {
        $salary = $this->calculateSalary($data);

        $taxSum = 0;
        foreach ($taxes as $key => $values) {
            $taxSum += $values["value"];
        }

        return round($salary - $taxSum, 2);
    }

    /**
     * Save data.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     *
     */
    public function store(Request $request) {
        $formData = $request->input('formData');
        $data = array_map('trim', $formData);

        [$response, $isValidated] = $this->validateForm($data);

        if (!$isValidated) {
            return response()->json($response, 400);
        }

        $result = $this->handle($data);

        $journal = new Journal;
        $journal->salary = $data["salary"];
        $journal->daysCount = $data["daysCount"];
        $journal->workDays = $data["workDays"];
        $journal->calendarYear = $data["calendarYear"];
        $journal->calendarMonth = $data["calendarMonth"];
        $journal->hasTax = $data["hasTax"];
        $journal->isPensioner = $data["isPensioner"] == '' ? false : true;
        $journal->isInvalid = $data["isInvalid"] == '' ? false : true;
        $journal->invalidDegree = $data["invalidDegree"];
        $journal->ipn = $result["taxes"]["ipn"]["value"];
        $journal->opv = $result["taxes"]["opv"]["value"];
        $journal->osms = $result["taxes"]["osms"]["value"];
        $journal->vosms = $result["taxes"]["vosms"]["value"];
        $journal->so = $result["taxes"]["so"]["value"];
        $journal->resultSalary = $result["salary"];
        $journal->save();

        $result["text"] = "Данные успешно сохранены";

        return response()->json($result, 200);
    }
}
