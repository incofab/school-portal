@component('mail::message')
Dear **{{$guardian->first_name}}**,

We hope this message finds you well. This is a gentle reminder that the **{{$receiptType->title}}** for your child/ward, **{{$student->user->last_name.' '.$student->user->first_name}}**, are due for payment. 

To ensure uninterrupted access to all school services, we kindly request that the payment be made as soon as possible. Below are the payment details:

@foreach ($feesToPay as $fee)
    {{$fee['title']}}: {{'₦' . number_format($fee['amount'])}}
@endforeach

**TOTAL: {{'₦' . number_format($totalFeesToPay)}}**

If you have already made this payment, please disregard this notice. If you have any questions or concerns, feel free to reach out to the school’s administration office.

Thank you for your attention to this matter. We greatly appreciate your prompt payment and continued support in your child’s education.


Regards,

Principal.
@endcomponent 
