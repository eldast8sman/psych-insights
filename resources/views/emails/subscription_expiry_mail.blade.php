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
            Subscription Expiry
          </p>
          <p
            style="
              text-align: center;
              color: #000000;
              margin-top: -20px;
              font-size: 14px;
            "
          >
            Your Subscription has expired
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
            We've noticed your Psych Insights Premium Subscription has expired. 
            <br />
            To keep enjoying all the great resources and features available through Psych Insights, simply renew your subscription by upgrading to our Premium Plan.
            Don't let your progress stall—continue your journey with us today! 
          </p>
        </td>
      </tr>
    </table>
  </td>
</tr>
@endsection
