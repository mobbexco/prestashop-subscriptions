#!/bin/sh

VER="1.0.1"

# Unified Version
PRESTAV="1.6-1.7"

if type 7z > /dev/null; then
    7z a -tzip "mobbex_subscriptions.$VER.ps-$PRESTAV.zip" mobbex_subscriptions
elif type zip > /dev/null; then
    zip mobbex_subscriptions.$VER.ps-$PRESTAV.zip -r mobbex_subscriptions
fi