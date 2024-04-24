@extends('emails.layouts.app')

@section('title')
  Auto Renewal Failed
@endsection

@section('subtitle')
  
@endsection

@section('content')

<tr>
  <td>
    <table width="100%">
      <tr>
        <td style="font-size: 14px">
          <p style="margin: 40px 0px">Hello {{ $name }},</p>
          <p style="margin-bottom: 40px">
            The Auto Renewal of your Subscription on PsychInsghts failed.
            <br />
            <strong>Reason: </strong><i>{{ $message }}</i>
          </p>
        </td>
      </tr>
    </table>
  </td>
</tr>
<tr>
  <td>
    <table width="100%">
      <tr>
        <td style="font-size: 14px">
          <p>
            Warm regards, <br />
            The PsychInsights Team
          </p>
        </td>
      </tr>
    </table>
  </td>
</tr>
@endsection
