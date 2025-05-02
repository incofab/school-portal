@component('mail::message')
Hello **{{$user->last_name .' '. $user->first_name}}**,

I am pleased to inform you that you have been offered admission into {{$institution->name}}.
Kindly click the link below to view and print your Admission Letter. 

[{{$url}}]({{$url}})

Visit the school for further documentation.
Congratulations.


Regards,

Principal.
@endcomponent