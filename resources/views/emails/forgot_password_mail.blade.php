@extends('emails.layouts.app')

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
            <p
              style="
                font-size: 40px;
                font-weight: 500;
                text-align: center;
              "
            >
              Reset your Password
            </p>

            <img
              src="https://psychinsight-email-icons.s3.us-east-2.amazonaws.com/checked.png"
              alt="checked-icon"
              width="50px"
            />
          </div>
          <p
            style="
              text-align: center;
              color: #000000;
              margin-top: -30px;
              font-size: 14px;
            "
          >
            Please reset your Password to access your Account
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
          <p style="margin: 40px 0px">Hello {{ $name }},</p>
          <p style="margin-bottom: 40px">
            Please click on the link below to reset your Password so that you can have acccess to your acccout
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
          <a
            href="{{ env('FRONTEND_URL') }}/#step2?token={{ $token }}"
            style="
              color: #ffffff;
              padding: 15px 55px;
              background: #207384;
              border-radius: 30px;
              text-decoration: none;
            "
            >Reset Password</a
          >
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
          <p style="margin-top: 40px">
            If you did not request this email, it's possible that
            someone, please make sure your account is secured as someone might be trying to illegally access your account.
          </p>
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