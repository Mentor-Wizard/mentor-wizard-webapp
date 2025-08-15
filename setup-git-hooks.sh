#!/bin/bash

# Setup script for Git hooks
# Run this script after cloning the repository to enable shared Git hooks

echo "Setting up Git hooks..."

# Configure Git to use the shared hooks directory
git config core.hooksPath .githooks

# Make sure all hooks are executable
chmod +x .githooks/*

echo "✅ Git hooks have been configured successfully!"
echo "The following hooks are now active:"
echo "  - pre-commit: Runs Rector and Pint code fixes"
echo "  - commit-msg: Validates commit messages with commitlint"
echo "  - pre-push: Validates branch names"
