<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectRequestFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array {
        return [
            'date' => 'required',
            'check_in' => 'required|date_format:H:i|before:check_out',
            'check_out' => 'required|date_format:H:i|after:check_in',

            'breaks.*' => 'nullable|array:break_start,break_end',
            'breaks.*.break_start' => 'nullable|date_format:H:i|after:check_in|before:check_out|required_with:breaks.*.break_end',
            'breaks.*.break_end' => 'nullable|date_format:H:i|before:check_out|after:breaks.*.break_start|required_with:breaks.*.break_start',

            'reason' => 'required|string|max:255'
        ];
    }

    public function messages() {
        return [
            'check_in.*' => '出勤時間もしくは退勤時間が不適切な値です',
            'check_out.*' => '出勤時間もしくは退勤時間が不適切な値です',

            'breaks.*' => '休憩時間が不適切な値です',
            'breaks.*.break_start' => '休憩時間が不適切な値です',
            'breaks.*.break_end' => '休憩時間もしくは退勤時間が不適切な値です',

            'reason.required' => '備考を記入してください',
        ];
    }
}
