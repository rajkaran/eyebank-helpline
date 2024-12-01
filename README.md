# Eyebank Helpline

This script runs periodically and fetches all new SMS messages received. An SMS is considered new if it hasn't been served or replied to. The script extracts the pincode from the SMS and identifies the geographically nearest eye bank. Once located, it forwards the SMS to the bank. The bank acknowledges the SMS, and the donor’s family is informed with the eye bank's details and instructions on when they can arrive to donate the eyes of the deceased person.

This script was developed alongside a web application for an NGO in India, aiming to connect eye donors and banks as quickly as possible, as eyes can only be donated within a few hours after death.

## Features

- Fetch new SMS messages
- Use regex to extract the pincode from SMS messages, as donors may not follow a specific SMS format
- Notify the eye bank
- Receive acknowledgment from the bank
- Notify the donor’s family with the eye bank details and the timeline for donation
