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
              Verify your email
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
            Please verify email to secure your account
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
            Welcome to PsychInsights! We are dedicated to optimizing
            your mental well-being for you to live a better life, and
            we're excited to have you on board. Before you can fully
            access our community, please verify your email address to
            ensure the security and authenticity of your account by entering
            this token on the Email Verification.
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
            href="{{ env('VERIFICATION_BASE') }}/thank-you.html?token={{ $token }}"
            style="
              color: #ffffff;
              padding: 15px 55px;
              background: #207384;
              border-radius: 30px;
              text-decoration: none;
            "
            >Verify email</a
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
            someone entered your email address by mistake. If that's the
            case, you can simply ignore this message. Thank you for
            choosing PsychInsights. We're here to support you on your
            journey to better mental health.
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