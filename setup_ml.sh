#!/bin/bash

echo "🚀 Setting up ML Forecasting System for Expense Tracker"
echo "======================================================"

# Check if Python 3 is installed
if ! command -v python3 &> /dev/null; then
    echo "❌ Python 3 is not installed. Please install Python 3.8+ first."
    echo "   On macOS: brew install python3"
    echo "   On Ubuntu: sudo apt-get install python3 python3-pip"
    exit 1
fi

echo "✅ Python 3 found: $(python3 --version)"

# Check if pip3 is installed
if ! command -v pip3 &> /dev/null; then
    echo "❌ pip3 is not installed. Please install pip3 first."
    exit 1
fi

echo "✅ pip3 found: $(pip3 --version)"

# Create ml_scripts directory if it doesn't exist
if [ ! -d "ml_scripts" ]; then
    echo "📁 Creating ml_scripts directory..."
    mkdir -p ml_scripts
fi

# Install Python dependencies
echo "📦 Installing Python ML dependencies..."
pip3 install -r ml_scripts/requirements.txt

echo "🔌 Installing MySQL connector..."
pip3 install mysql-connector-python

if [ $? -eq 0 ]; then
    echo "✅ Python dependencies installed successfully!"
else
    echo "❌ Failed to install Python dependencies. Please check the error above."
    exit 1
fi

# Make Python script executable
echo "🔧 Making ML script executable..."
chmod +x ml_scripts/forecast.py

# Test Python script
echo "🧪 Testing ML script..."
python3 ml_scripts/forecast.py --help

if [ $? -eq 0 ]; then
    echo "✅ ML script is working correctly!"
else
    echo "❌ ML script test failed. Please check the error above."
    exit 1
fi

echo ""
echo "🎉 ML Forecasting System setup complete!"
echo ""
echo "Next steps:"
echo "1. Make sure your Laravel application is running"
echo "2. Visit /ml-accuracy to see the ML dashboard"
echo "3. Visit /forecast to see ML-powered forecasts"
echo ""
echo "Note: ML forecasting requires at least 6 months of expense data per category"
echo "to work effectively. Categories with insufficient data will fall back to"
echo "statistical forecasting methods." 