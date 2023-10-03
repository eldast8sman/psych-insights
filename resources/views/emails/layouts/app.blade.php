<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HTML Email</title>
    <style type="text/css">
      body {
        margin: 0;
        background-color: #cccccc;
      }
      table {
        border-spacing: 0;
      }
      td {
        padding: 0;
      }
      img {
        border: 0;
      }

      .wrapper {
        width: 100%;
        table-layout: fixed;
        background-color: #cccccc;
        padding-bottom: 40px;
      }
      .main {
        background-color: #ffffff;
        margin: 0 auto;
        width: 100%;
        max-width: 600px;
        border-spacing: 0;
        font-family: sans-serif;
        color: #4a4a4a;
      }
      @media screen and (max-width: 600px) {
      }
    </style>
  </head>
  <body>
    <center class="wrapper">
      <table class="main" width="100%" style="padding: 30px">
        <tr>
          <td>
            <table width="100%">
              <tr>
                <td
                  style="
                    display: flex;
                    align-items: center;
                    justify-content: space-around;
                  "
                >
                  <!-- <div
                    style="
                      display: flex;
                      align-items: center;
                      justify-content: space-around;
                    "
                  > -->
                  <a
                    href="#"
                    style="
                      display: flex;
                      align-items: center;
                      gap: 5px;
                      text-decoration: none;
                      color: #2e2f32;
                    "
                    title="PsychInsights"
                  >
                    <img
                      src="https://psychinsight-email-icons.s3.us-east-2.amazonaws.com/logo+(1).png"
                      alt="Psych-logo"
                    />
                    <h2>PsychInsights</h2>
                  </a>
                  <img
                    src="https://psychinsight-email-icons.s3.us-east-2.amazonaws.com/line.png"
                    alt="line"
                  />
                  <!-- </div> -->
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td>
            <table width="100%">
              <tr>
                <td
                  style="
                    display: flex;
                    text-align: center;
                    align-items: center;
                    gap: 10px;
                    justify-content: center;
                    color: #000000;
                  "
                >
                  <!-- <div
                    style="
                      display: flex;
                      text-align: center;
                      align-items: center;
                      gap: 10px;
                      justify-content: center;
                      color: #000000;
                    "
                  > -->
                  <p
                    style="
                      font-size: 40px;
                      font-weight: 500;
                      text-align: center;
                    "
                  >
                    @yield('title')
                  </p>

                  <img
                    src="https://psychinsight-email-icons.s3.us-east-2.amazonaws.com/checked.png"
                    alt="checked-icon"
                    width="50px"
                  />
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- mmmmmm -->
        <tr>
          <td>
            <table width="100%">
              <tr>
                <td>
                  <p
                    style="
                      text-align: center;
                      color: #000000;
                      margin-top: -30px;
                      font-size: 14px;
                    "
                  >
                    @yield('subtitle')
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <!-- mmmmmm -->

       @yield('content')

        <tr>
          <td>
            <table width="90%" style="margin: auto">
              <tr>
                <td
                  style="
                    text-align: center;
                    background-color: #f0f1f9;
                    border-radius: 10px;
                  "
                >
                  <a href="#">
                    <img
                      src="https://psychinsight-email-icons.s3.us-east-2.amazonaws.com/facebook.png"
                      alt="Facebook"
                    />
                  </a>
                  <a href="#" style="margin: 0px 15px">
                    <img
                      src="https://psychinsight-email-icons.s3.us-east-2.amazonaws.com/twitter.png"
                      alt="twitter.svg"
                    />
                  </a>
                  <a href="#">
                    <img
                      src="https://psychinsight-email-icons.s3.us-east-2.amazonaws.com/facebook.png"
                      alt="Facebook"
                    />
                  </a>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </center>
  </body>
</html>

<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HTML Email</title>
    <style type="text/css">
      body {
        margin: 0;
        background-color: #cccccc;
      }
      table {
        border-spacing: 0;
      }
      td {
        padding: 0;
      }
      img {
        border: 0;
      }

      .wrapper {
        width: 100%;
        table-layout: fixed;
        background-color: #cccccc;
        padding-bottom: 40px;
      }
      .main {
        background-color: #ffffff;
        margin: 0 auto;
        width: 100%;
        max-width: 600px;
        border-spacing: 0;
        font-family: sans-serif;
        color: #4a4a4a;
      }
      @media screen and (max-width: 600px) {
      }
    </style>
  </head>
  <body>
    <center class="wrapper">
      <table class="main" width="100%" style="padding: 30px">
        <tr>
          <td>
            <table width="100%">
              <tr>
                <td style="text-align: center">
                  <!-- <a
                    href="#"
                    style="
                      display: flex;
                      align-items: center;
                      gap: 5px;
                      text-decoration: none;
                      color: #2e2f32;
                    "
                    title="PsychInsights"
                  >
                    <img
                      src="https://psychinsight-email-icons.s3.us-east-2.amazonaws.com/logo+(1).png"
                      alt="Psych-logo"
                    />
                    <h2>PsychInsights</h2>
                  </a> -->

                  <img
                    src="https://psychinsight-email-icons.s3.us-east-2.amazonaws.com/brand.png"
                    alt="Psych-logo"
                  />
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- hmmmm -->
        <!-- <tr>
          <td>
            <table width="100%">
              <tr>
                <td style="text-align: center">
                  <img
                    src="https://psychinsight-email-icons.s3.us-east-2.amazonaws.com/checked.png"
                    alt="checked-icon"
                    width="50px"
                  />
                </td>
              </tr>
            </table>
          </td>
        </tr> -->
        <!-- hmmmm -->

        <tr>
          <td>
            <table width="100%">
              <tr>
                <td>
                  <p
                    style="
                      font-size: 40px;
                      font-weight: 500;
                      text-align: center;
                    "
                  >
                    @yield('title')
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- mmmmmm -->
        <tr>
          <td>
            <table width="100%">
              <tr>
                <td>
                  <p
                    style="
                      text-align: center;
                      color: #000000;
                      margin-top: -30px;
                      font-size: 14px;
                    "
                  >
                    @yield('subtitle')
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <!-- mmmmmm -->

        @yield('content')

        <tr>
          <td>
            <table width="90%" style="margin: auto">
              <tr>
                <td
                  style="
                    text-align: center;
                    background-color: #f0f1f9;
                    border-radius: 10px;
                  "
                >
                  <a href="#">
                    <img
                      src="https://psychinsight-email-icons.s3.us-east-2.amazonaws.com/facebook.png"
                      alt="Facebook"
                    />
                  </a>
                  <a href="#" style="margin: 0px 15px">
                    <img
                      src="https://psychinsight-email-icons.s3.us-east-2.amazonaws.com/twitter.png"
                      alt="twitter.svg"
                    />
                  </a>
                  <a href="#">
                    <img
                      src="https://psychinsight-email-icons.s3.us-east-2.amazonaws.com/facebook.png"
                      alt="Facebook"
                    />
                  </a>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </center>
  </body>
</html>