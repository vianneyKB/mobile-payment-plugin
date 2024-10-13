jQuery(document).ready(function($) {
    $('#pay_with_mpesa').on('click', function(e) {
        e.preventDefault();

        let orderId = $(this).data('order-id');
        let mpesaPaymentData = {
            action: 'process_mpesa_payment',
            order_id: orderId,
            security: mvpg_vars.mpesa_nonce, // Add nonce here
        };

        $.ajax({
            url: mvpg_vars.ajax_url,
            type: 'POST',
            data: mpesaPaymentData,
            success: function(response) {
                if (response.success) {
                    alert('Payment successful!');
                    window.location.href = response.redirect_url;
                } else {
                    alert('Payment failed. Please try again.');
                }
            },
            error: function(error) {
                console.log('Error:', error);
                alert('Payment error. Please contact support.');
            }
        });
    });
      /**
     * Handle Airtel Money Payment
     */
       $('#pay_with_airtel').on('click', function(e) {
        e.preventDefault();

        let orderId = $(this).data('order-id');
        let country = $('#airtel_country').val(); // Select country from dropdown

        $.ajax({
            url: mvpg_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'process_airtel_payment',
                order_id: orderId,
                country: country,
                security: mvpg_vars.airtel_nonce // Add nonce here
            },
            success: function(response) {
                if (response.success) {
                    alert('Airtel payment successful!');
                    window.location.href = response.redirect_url;
                } else {
                    alert('Airtel payment failed: ' + response.message);
                }
            },
            error: function(error) {
                console.log('Airtel payment error:', error);
                alert('An error occurred. Please contact support.');
            }
        });
    });
        /**
     * Handle Orange Money Payment
     */
         $('#pay_with_orange').on('click', function(e) {
            e.preventDefault();
    
            let orderId = $(this).data('order-id');
            let country = $('#orange_country').val(); // Select country from dropdown
    
            $.ajax({
                url: mvpg_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'process_orange_payment',
                    order_id: orderId,
                    country: country,
                    security: mvpg_vars.orange_nonce // Add nonce here
                },
                success: function(response) {
                    if (response.success) {
                        alert('Orange payment successful!');
                        window.location.href = response.redirect_url;
                    } else {
                        alert('Orange payment failed: ' + response.message);
                    }
                },
                error: function(error) {
                    console.log('Orange payment error:', error);
                    alert('An error occurred. Please contact support.');
                }
            });
        });
});
