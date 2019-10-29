@public_subscriptions
Feature: Creating a new subscription with a custom amount using Stripe SCA Checkout.
  In order to pay for the service
  As a HTTP Client
  I want to make a request against subscription create endpoint

  Background:
    Given the system has a payment method "Stripe Checkout" with a code "stripe_checkout" and Stripe Checkout gateway

  Scenario: Create a new recurring subscription with a custom amount
    When I add "Authorization" header equal to null
    And I add "Content-Type" header equal to "application/json"
    And I add "Accept" header equal to "application/json"
    And I send a "POST" request to "/public-api/v1/subscriptions/" with body:
    """
    {
        "type": "recurring",
        "mode": "donation",
        "interval": "1 month",
        "amount": 3500,
        "currency_code": "PLN",
        "method": "stripe_checkout",
        "metadata": {
        	"email": "tom@example.com",
        	"productId": "prod_id"
        }
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json"
    And the JSON nodes should contain:
      | currency_code                         | PLN                         |
      | amount                                | 3500                        |
      | interval                              | 1 month                     |
      | type                                  | recurring                   |
      | state                                 | new                         |
      | purchase_state                        | completed                   |
      | payment_state                         | awaiting_payment            |
      | method.code                           | stripe_checkout             |
      | metadata.email                        | tom@example.com             |
      | metadata.productId                    | prod_id                     |
    And the JSON node "method.translations" should exist
    And the JSON node "metadata" should have 2 elements
    And the JSON node "purchase_completed_at" should not be null
    And the JSON node "start_date" should be null
    And the JSON node "created_at" should not be null
    And the JSON node "updated_at" should not be null
    And the JSON node "items" should not exist
    And the JSON node "payments" should not exist
    And the JSON node "_links" should not exist
    And the JSON node "method.gateway_config" should not exist
    And the JSON node "enabled" should not exist
    And the JSON node "created_at" should not exist
    And the JSON node "updated_at" should not exist

  Scenario: Create a new recurring subscription without amount
    When I add "Authorization" header equal to null
    And I add "Content-Type" header equal to "application/json"
    And I add "Accept" header equal to "application/json"
    And I send a "POST" request to "/public-api/v1/subscriptions/" with body:
    """
    {
        "type": "recurring",
        "mode": "donation",
        "interval": "1 month",
        "currency_code": "PLN",
        "method": "stripe_checkout",
        "metadata": {
        	"email": "tom@example.com",
        	"productId": "prod_id"
        }
    }
    """
    Then the response status code should be 400
    And the JSON should be equal to:
    """
    {
       "code":400,
       "message":"Validation Failed",
       "errors":{
          "children":{
             "amount":{
                "errors":[
                   "The amount field must be set in this mode."
                ]
             },
             "mode":{

             },
             "currencyCode":{

             },
             "plan":{

             },
             "interval":{

             },
             "type":{

             },
             "startDate":{

             },
             "metadata":{

             },
             "method":{
                "children":[
                   {

                   }
                ]
             }
          }
       }
    }
    """

  Scenario: Create a new recurring subscription without interval.
    When I add "Authorization" header equal to null
    And I add "Content-Type" header equal to "application/json"
    And I add "Accept" header equal to "application/json"
    And I send a "POST" request to "/public-api/v1/subscriptions/" with body:
    """
    {
        "type": "recurring",
        "mode": "donation",
        "amount": 3500,
        "currency_code": "PLN",
        "method": "stripe_checkout",
        "metadata": {
        	"email": "tom@example.com",
        	"productId": "prod_id"
        }
    }
    """
    Then the response status code should be 400
    And the JSON should be equal to:
    """
    {
       "code":400,
       "message":"Validation Failed",
       "errors":{
          "children":{
             "amount":{

             },
             "mode":{

             },
             "currencyCode":{

             },
             "plan":{

             },
             "interval":{
                "errors":[
                   "The interval field must be set in this mode."
                ]
             },
             "type":{

             },
             "startDate":{

             },
             "metadata":{

             },
             "method":{
                "children":[
                   {

                   }
                ]
             }
          }
       }
    }
    """

  Scenario: Create a new recurring subscription with plan field set.
    When I add "Authorization" header equal to null
    And I add "Content-Type" header equal to "application/json"
    And I add "Accept" header equal to "application/json"
    And I send a "POST" request to "/public-api/v1/subscriptions/" with body:
    """
    {
        "type": "recurring",
        "plan": "test",
        "mode": "donation",
        "amount": 3500,
        "currency_code": "PLN",
        "method": "stripe_checkout",
        "metadata": {
        	"email": "tom@example.com",
        	"productId": "prod_id"
        }
    }
    """
    Then the response status code should be 400
    And the JSON should be equal to:
    """
    {
       "code":400,
       "message":"Validation Failed",
       "errors":{
          "children":{
             "amount":{

             },
             "mode":{

             },
             "currencyCode":{

             },
             "plan":{
                "errors":[
                   "The plan field must not be set in this mode."
                ]
             },
             "interval":{

             },
             "type":{

             },
             "startDate":{

             },
             "metadata":{

             },
             "method":{
                "children":[
                   {

                   }
                ]
             }
          }
       }
    }
    """