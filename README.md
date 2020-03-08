### Pagarme-Laravel

###### References:

    - Get your keys e sign a comercial contract with pagarme
    - Dashboard https://dashboard.pagar.me/
    - Docs https://docs.pagar.me/

##### Intallation

        * composer required lojazone/pagarme
        * php artisan vendor:publish --tag=lojazone-pagarme-config
        * Add your config/app.php `Lojazone\Pagarme\Providers\PagarmeServiceProvider::class`
        * Publish the config file and Define your keys, environment
        * or define keys in your .env file

###### .env file
* <small>API_KEY_PAGARME_SANDBOX=</small>
* <small>CRYPTO_KEY_PAGARME_SANDBOX=</small>
* <small>API_KEY_PAGARME=</small>
* <small>CRYPTO_KEY_PAGARME=</small>        
        
##### Exemples to use:

<code>use \Lojazone\Pagarme;</code>

<code>$pagarme = new Pagarme();</code>

<code>$pagarme->getCustomerList();</code>
