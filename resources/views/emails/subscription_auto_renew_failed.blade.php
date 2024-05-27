@extends('emails.layouts.newapp')

@section('content')

<tr>
  <td>
    <table width="100%">
      <tr>
        <td>
          <p
            style="
              text-align: center;
              font-size: 40px;
              font-weight: 500;
              color: #000000;
            "
          >
            Auto Renewal Failed
          </p>
          <p
            style="
              text-align: center;
              color: #000000;
              margin-top: -20px;
              font-size: 14px;
            "
          >
            We couldn't renew your Subscription
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
          <p style="margin-top: 30px">
            Hello <span>{{ $name }}</span>,
          </p>
          <p style="margin-bottom: 20px; text-align: left">
            Oops! There was an issue with auto-renewing your subscription to PsychInsights.
            Please update your payment details to continue enjoying seamless access to all our features without interruption. 
            <br /> 
            <strong>Reason: </strong><i>{{ $message }}</i>
          </p>
        </td>
      </tr>
    </table>
  </td>
</tr>
@endsection
