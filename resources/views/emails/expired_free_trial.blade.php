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
            You've Completed Your Premium Trial
          </p>
          <p
            style="
              text-align: center;
              color: #000000;
              margin-top: -20px;
              font-size: 14px;
            "
          >
            Here's What's Next
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
            Thank you for completing your 7-day free trial of Psych Insights Premium! We hope you found value in the enhanced features designed to support your mental well-being.
            <br />
            As of today, your account has been automatically downgraded to our Basic (Free) version, where you can still access many essential tools to continue your mental health journey.
            <br />
            However, if you'd like to regain access to the full range of Premium features, you can easily upgrade back to Premium at any time by clicking the “Upgrade Now” button at the top of your home page.
          </p>
          <p>
            <strong>With the Premium plan, you'll enjoy:</strong>
            <ul>
              <li>Exclusive video content</li>
              <li>More comprehensive assessments and further personalisation</li>
              <li>Distress scores to track your stress, anxiety, and depression levels over time</li>
              <li>Resource Library and Answers Archive to access previous resources and responses</li>
              <li>Expanded resource library with even more valuable tools</li>
            </ul>

            We're grateful to have you in our community and are here to support you every step of the way. If you have any questions or need assistance, feel free to reach out via the 'contact us' option.
          </p>
          <p style="margin-bottom: 20px; text-align: left">
            Take care,
            <br />
            The Psych Insights Team
          </p>
        </td>
      </tr>
    </table>
  </td>
</tr>
@endsection
