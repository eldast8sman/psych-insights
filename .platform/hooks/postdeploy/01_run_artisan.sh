#!/bin/bash

# Download env.json file from S3 bucket
aws s3 cp s3://psychenv/env.json /tmp/env.json

# Parse env.json and create .env file
cat /tmp/env.json | jq -r 'to_entries[] | "\(.key)=\(.value)"' > /var/app/current/.env

# Navigate to the Laravel app directory
cd /var/app/current

# Download psychinsightsapp.json file from S3 bucket
aws s3 cp s3://psychinsightsapp/psychinsightsapp.json /tmp/psychinsightsapp.json

# Create directory if it doesn't exist
mkdir -p storage/app/public/fcm

# Copy psychinsightsapp.json to storage/app/public/fcm folder
cp /tmp/psychinsightsapp.json storage/app/public/fcm

# Run Laravel Artisan commands
php artisan migrate
php artisan config:clear
php artisan cache:clear
php artisan view:clears
