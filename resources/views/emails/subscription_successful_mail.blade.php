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
            Welcome to the Psych Insights Premium community!
            <br />
            We are thrilled to have you on board.
            <br />
            With your premium subscription, you now have access to exclusive features and insights designed to help you better understand and manage your mental well-being.
            <br />
            We are committed to supporting you every step of the way. If you have any questions or need help navigating your premium features, feel free to reach out to us at [Support Email].
            <br />
            Thank you for choosing Psych Insights. We are so honoured to be supporting you on your mental health journey.
            <br />
            Best regards,
            <br />
            Psych Insights Team.
          </p>
        </td>
      </tr>
    </table>
  </td>
</tr>
@endsection
