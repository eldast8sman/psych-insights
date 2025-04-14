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
          Your Psych Insights Premium Subscription Has Been Automatically Renewed!
          </p>
          <p
            style="
              text-align: center;
              color: #000000;
              margin-top: -20px;
              font-size: 14px;
            "
          >
            We are thrilled having you with us
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
            Hi <span>{{ $name }}</span>,
          </p>
          <p style="margin-bottom: 20px; text-align: left">
            We hope you're continuing to enjoy your <strong>Psych Insights Premium</strong> experience! This is a reminder that your subscription has been automatically renewed for another term.
            <br />
            <br />
            Your Premium benefits will continue without interruption.
            <br />
            <br />
            If you'd like to manage or cancel your subscription, you can do so at any time by navigating to the <strong>Subscription Status</strong> section in the app, then selecting <strong>Cancel Subscription</strong>.
            <br />
            <br />
            Warm regards,
            <br />
            The Psych Insights Team.
          </p>
        </td>
      </tr>
    </table>
  </td>
</tr>
@endsection
