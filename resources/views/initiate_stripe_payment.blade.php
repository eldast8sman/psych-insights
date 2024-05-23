<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Test Stripe Payment</title>
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <form id="payment-form">
        <div id="payment-element">
          <!-- Elements will create form elements here -->
        </div>
        <button id="submit">Submit</button>
        <div id="error-message">
          <!-- Display error message to your customers here -->
        </div>
    </form>

    <script>
        const stripe = Stripe('pk_test_51NpMgVLmDhaafYBlK4DyGBCHxDV5Z5Txdmjqp1aiEbDnR9Ku7RL3DSj3X7o9SPKiykT0MjSlu4RgGfVyKIGLCHKP00kFY14bLI');
        const ClientSecret = "pi_3PJ0BULmDhaafYBl06Pn2N8a_secret_rbAGGtlOomYKFglIM2UtkekWL";

        const options = {
            clientSecret: ClientSecret,
            // Fully customizable with appearance API.
            appearance: {/*...*/},
        };

        // Set up Stripe.js and Elements to use in checkout form, passing the client secret obtained in a previous step
        const elements = stripe.elements(options);

        // Create and mount the Payment Element
        const paymentElement = elements.create('payment');
        paymentElement.mount('#payment-element');

        const form = document.getElementById('payment-form');

        form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const {error} = await stripe.confirmPayment({
            //`Elements` instance that was used to create the Payment Element
            elements,
            confirmParams: {
            return_url: 'http://127.0.0.1:8000/confirm-payment',
            },
        });

        if (error) {
            // This point will only be reached if there is an immediate error when
            // confirming the payment. Show error to your customer (for example, payment
            // details incomplete)
            const messageContainer = document.querySelector('#error-message');
            messageContainer.textContent = error.message;
        } else {
            // Your customer will be redirected to your `return_url`. For some payment
            // methods like iDEAL, your customer will be redirected to an intermediate
            // site first to authorize the payment, then redirected to the `return_url`.
        }
        });
    </script>
</body>
</html>