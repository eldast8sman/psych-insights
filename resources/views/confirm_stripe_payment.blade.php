<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Confirm Payment</title>
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <p id="message">

    </p>

    <script>
        // Initialize Stripe.js using your publishable key
        const stripe = Stripe('pk_test_51NpMgVLmDhaafYBlK4DyGBCHxDV5Z5Txdmjqp1aiEbDnR9Ku7RL3DSj3X7o9SPKiykT0MjSlu4RgGfVyKIGLCHKP00kFY14bLI');

        // Retrieve the "payment_intent_client_secret" query parameter appended to
        // your return_url by Stripe.js
        const clientSecret = new URLSearchParams(window.location.search).get(
        'payment_intent_client_secret'
        );
        
        // const message = document.querySelector('#message');
        // message.innerText = clientSecret

        // Retrieve the PaymentIntent
        stripe.retrievePaymentIntent(clientSecret).then(({paymentIntent}) => {
            const message = document.querySelector('#message')
            console.log(paymentIntent);
            
            // Inspect the PaymentIntent `status` to indicate the status of the payment
            // to your customer.
            //
            // Some payment methods will [immediately succeed or fail][0] upon
            // confirmation, while others will first enter a `processing` state.
            //
            // [0]: https://stripe.com/docs/payments/payment-methods#payment-notification
            switch (paymentIntent.status) {
                case 'succeeded':
                message.innerText = 'Success! Payment received.';
                break;

                case 'processing':
                message.innerText = "Payment processing. We'll update you when payment is received.";
                break;

                case 'requires_payment_method':
                message.innerText = 'Payment failed. Please try another payment method.';
                // Redirect your user back to your payment page to attempt collecting
                // payment again
                break;

                default:
                message.innerText = 'Something went wrong.';
                break;
            }
        });
    </script>
</body>
</html>