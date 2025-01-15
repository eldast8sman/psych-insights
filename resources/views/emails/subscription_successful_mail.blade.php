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
            Welcome to Psych Insights Premium!
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
            Welcome to the <strong>Psych Insights Premium</strong> community!  We are absolutely thrilled to have you on board.
            <br />
            With your premium subscription, you've unlocked a suite of exclusive features and tools designed to empower your mental health journey and provide deeper insights into your well-being
            <br />
            We're here to support you every step of the way. If you have any questions or need assistance with your Premium features, don't hesitate to reach out to us at <i>support@psychinsightsapp.com</i>
            <br />
            Thank you for choosing <strong>Psych Insights</strong> - It's an honour to be part of your journey toward greater mental well-being
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
