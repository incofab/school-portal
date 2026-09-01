@component('mail::message')
Dear **{{$guardian->first_name}}**,

This is a friendly reminder that {{$fee->title}} for {{$fee->term ? "{$fee->term?->value} Term, " : ''}} {{$fee->academicSession->title}} 
is due. Please ensure timely payment to avoid any disruptions to your child's academic activities.

## Payment Details:
@foreach ($fee->fee_items as $feeItem)
- {{$feeItem['title']}}: ₦{{number_format($feeItem['amount'])}}
@endforeach

**Total Amount: ₦{{number_format($fee->amount)}}**

@component('mail::button', ['url' => route('public.student-fee-payment', [$institution->code, $student->code])])
View fees and pay securely
@endcomponent

Thank you for your prompt attention to this matter.

Best regards,

{{$institution->name}}  
{{$institution->phone}}

@endcomponent 
