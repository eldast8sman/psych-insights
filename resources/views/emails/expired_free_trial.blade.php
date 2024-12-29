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
            Thank you for trying out the 7-day free trial of <strong>Psych Insights Premium!</strong> We hope the enhanced features added value to your mental well-being journey.
            <br />
            <br />
            As of today, your account has been automatically transitioned to our <strong>Basic (Free)</strong> plan.
            You'll still have access to many essential tools to support your mental health. But if you're missing the added benefits of Premium,
            upgradig is quick and easy. Simply click the <strong>"Upgrade Now"</strong> button at the top of your home page to unlock Premium features again.
          </p>
          <p>
            With <strong>Psych Insights Premium</strong>, you'll gain access to:
            <ul>
              <li><strong>Exclusive video content</strong></li>
              <li>More comprehensive assessments and personalised insights</li>
              <li><strong>Distress scores</strong> to track your stress, anxiety, and depression over time</li>
              <li><strong>Resource Library and Answers Archive</strong> to revisit past resources and responses</li>
              <li><strong>Weekly resource refreshes</strong> (compared to fortnightly with Basic) and access to <strong>more resourceseach time,</strong>
                ensuring you have an ever-growing libraryof valuable tools to support your mental health journey  
              </li>
            </ul>

            We're so grateful to have you as part of our community. If you have questions or need assistance, don't hesitate to reach out through the <strong>'Contact Us'</strong> option
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
