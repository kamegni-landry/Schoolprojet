#!/usr/bin/env bash
# Copies .env.replit → .env and appends SMTP secrets from Replit environment
cp .env.replit .env

if [ -n "$MAIL_USERNAME" ]; then
    printf 'MAIL_USERNAME="%s"\n' "$MAIL_USERNAME" >> .env
fi

if [ -n "$MAIL_PASSWORD" ]; then
    printf 'MAIL_PASSWORD="%s"\n' "$MAIL_PASSWORD" >> .env
fi
