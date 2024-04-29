@extends('emails.layouts.newapp')

@section('content')
<tr>
  <td>
    <table width="100%">
      <tr>
        <td>
          <div
            style="
              display: flex;
              text-align: center;
              align-items: center;
              gap: 10px;
              justify-content: center;
              color: #000000;
            "
          >
            <p style="font-size: 40px; font-weight: 500">
              Forgot password?
            </p>
            <img
              src="https://psychinsight-email-icons.s3.us-east-2.amazonaws.com/padlock.png"
              alt="padlock-icon"
              width="50px"
            />
          </div>
          <p
            style="
              text-align: center;
              color: #000000;
              margin-top: -20px;
              font-size: 14px;
            "
          >
            Reset Your Psych Insights Password
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
          <p style="margin: 30px 0px">
            Hello <span>{{ $name }}</span>,
          </p>
          <p style="margin-bottom: 40px; text-align: center">
            To set up a new password to your Psych Insights account,
            enter this code on your device.
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
        <td style="text-align: center">
          <p>Your code:</p>
          <span
            style="
              color: #ffffff;
              padding: 15px 55px;
              background: #207384;
              border-radius: 10px;
              text-decoration: none;
              letter-spacing: 10px;
              font-size: 25px;
              font-weight: bold;
            "
          >
            {{ $token }}
          </span>
        </td>
      </tr>
    </table>
  </td>
</tr>
@endsection