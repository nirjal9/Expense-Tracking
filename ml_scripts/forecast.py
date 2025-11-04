#!/usr/bin/env python3
"""
Expense Forecasting ML Script
Uses multiple ML algorithms to predict future expenses
"""

import pandas as pd
import numpy as np
import sqlite3
import json
import sys
import argparse
import os
import joblib
from datetime import datetime, timedelta
from sklearn.linear_model import LinearRegression
from sklearn.ensemble import RandomForestRegressor
from sklearn.preprocessing import StandardScaler, MinMaxScaler
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from sklearn.model_selection import train_test_split, cross_val_score
import warnings
warnings.filterwarnings('ignore')


try:
    import mysql.connector
    MYSQL_AVAILABLE = True
except ImportError:
    MYSQL_AVAILABLE = False
    print("Warning: mysql-connector-python not installed. Install with: pip3 install mysql-connector-python")

class ExpenseForecaster:
    def __init__(self, db_config=None, db_path=None, model_storage_path=None):
        self.db_config = db_config
        self.db_path = db_path
        self.db_type = 'mysql' if db_config else 'sqlite'
        self.model_storage_path = model_storage_path or 'storage/app/ml_models'
        self.models = {
            'linear': LinearRegression(),
            'random_forest': RandomForestRegressor(n_estimators=100, random_state=42, max_depth=10)
        }
        self.scaler = StandardScaler()
        
        # Ensure model storage directory exists
        os.makedirs(self.model_storage_path, exist_ok=True)
        
    def get_user_data(self, user_id, category_id, target_month=None, target_year=None):
        """Fetch user expense data from database, optionally filtered up to target month"""
        try:
            print(f"DEBUG: Attempting to connect to database for user {user_id}, category {category_id}", file=sys.stderr)
            
            if self.db_type == 'mysql':
                if not MYSQL_AVAILABLE:
                    print("ERROR: MySQL connector not available", file=sys.stderr)
                    return None
                # Connect to MySQL
                print(f"DEBUG: Connecting to MySQL: {self.db_config['host']}:{self.db_config['port']}/{self.db_config['database']}", file=sys.stderr)
                conn = mysql.connector.connect(
                    host=self.db_config['host'],
                    port=self.db_config['port'],
                    database=self.db_config['database'],
                    user=self.db_config['username'],
                    password=self.db_config['password']
                )
                print("DEBUG: MySQL connection successful", file=sys.stderr)
            else:
                # SQLite connection
                if not os.path.exists(self.db_path):
                    print(f"ERROR: SQLite database not found at {self.db_path}", file=sys.stderr)
                    return None
                conn = sqlite3.connect(self.db_path)
                print("DEBUG: SQLite connection successful", file=sys.stderr)

            # Build query with optional date filter
            if target_month and target_year:
                # Only get data up to (but not including) the target month
                target_date_str = f"{target_year}-{target_month:02d}-01"
                query = f"""
                SELECT e.amount, e.date
                FROM expenses e
                WHERE e.user_id = {user_id} AND e.category_id = {category_id}
                AND e.date < '{target_date_str}'
                ORDER BY e.date
                """
                print(f"DEBUG: Filtering data up to {target_year}-{target_month:02d} (exclusive)", file=sys.stderr)
            else:
                # Get all data if no target specified
                query = f"""
                SELECT e.amount, e.date
                FROM expenses e
                WHERE e.user_id = {user_id} AND e.category_id = {category_id}
                ORDER BY e.date
                """
            
            print(f"DEBUG: Executing query: {query}", file=sys.stderr)

            df = pd.read_sql_query(query, conn)
            print(f"DEBUG: Query returned {len(df)} rows", file=sys.stderr)
            conn.close()

            if df.empty:
                print("DEBUG: DataFrame is empty", file=sys.stderr)
                return None

            # Convert date column
            df['date'] = pd.to_datetime(df['date'])
            df = df.sort_values('date')
            print(f"DEBUG: Processed {len(df)} rows of data", file=sys.stderr)
            
            # Aggregate expenses by month for forecasting monthly totals
            df['month_key'] = df['date'].dt.to_period('M')
            df_monthly = df.groupby('month_key', as_index=False).agg({
                'amount': 'sum'  # Sum expenses per month
            })
            df_monthly['date'] = pd.to_datetime(df_monthly['month_key'].astype(str))
            df_monthly = df_monthly[['date', 'amount']].sort_values('date')
            
            print(f"DEBUG: Aggregated to {len(df_monthly)} monthly data points", file=sys.stderr)

            # Provide defaults for optional engineered fields
            df_monthly['budget_percentage'] = 0.0
            df_monthly['income'] = 0.0

            return df_monthly
            
        except Exception as e:
            print(f"ERROR in get_user_data: {str(e)}", file=sys.stderr)
            return None
    
    def prepare_features(self, df):
        """Create features for ML model"""
        if len(df) < 3:  # Need at least 3 data points for meaningful features (reduced from 6 for predictions)
            return None, None
            
        # Ensure optional columns exist
        if 'budget_percentage' not in df.columns:
            df['budget_percentage'] = 0.0
        if 'income' not in df.columns:
            df['income'] = 0.0
            
        # Basic features
        df['month'] = df['date'].dt.month
        df['year'] = df['date'].dt.year
        df['day_of_week'] = df['date'].dt.dayofweek
        
        # Time-based features
        df['previous_month'] = df['amount'].shift(1)
        df['previous_2months'] = df['amount'].shift(2)
        df['previous_3months'] = df['amount'].shift(3)
        
        # Use only past values for rolling features to avoid leakage
        past_amount = df['amount'].shift(1)
        df['rolling_3m'] = past_amount.rolling(3, min_periods=2).mean()  # Reduced min_periods
        df['rolling_6m'] = past_amount.rolling(6, min_periods=3).mean()  # Reduced min_periods
        # Only add rolling_12m if we have enough data (12+ months)
        if len(df) >= 12:
            df['rolling_12m'] = past_amount.rolling(12, min_periods=12).mean()
        else:
            df['rolling_12m'] = df['rolling_6m']  # Use 6m as fallback
        
        # Volatility features based on past values only
        df['rolling_std_3m'] = past_amount.rolling(3, min_periods=2).std()  # Reduced min_periods
        df['rolling_std_6m'] = past_amount.rolling(6, min_periods=3).std()  # Reduced min_periods
        
        # Trend feature on past 3 months only
        def slope_last_n(values):
            n = len(values)
            if n < 2:
                return 0.0
            x = np.arange(n)
            m, _ = np.polyfit(x, values, 1)
            return m
        df['trend_3m'] = past_amount.rolling(3, min_periods=3).apply(slope_last_n, raw=True)
        
        # Seasonal features
        df['is_holiday_season'] = df['month'].isin([11, 12, 1])  # Nov, Dec, Jan
        df['is_summer'] = df['month'].isin([6, 7, 8])
        
        # Budget features
        df['budget_percentage'] = df['budget_percentage'].fillna(0)
        df['budget_amount'] = (df['budget_percentage'] / 100) * df['income'].fillna(0)
        
        # Don't dropna() here - we'll handle it more carefully after feature extraction
        # This allows rolling windows with reduced min_periods to still work
            
        # Select feature columns
        feature_columns = [
            'month', 'day_of_week', 'previous_month', 'previous_2months', 'previous_3months',
            'rolling_3m', 'rolling_6m', 'rolling_12m', 'rolling_std_3m', 'rolling_std_6m',
            'trend_3m', 'is_holiday_season', 'is_summer', 'budget_percentage'
        ]
        
        # Filter to only existing columns
        available_features = [col for col in feature_columns if col in df.columns]
        
        # Extract features and targets, then drop rows with NaN values
        feature_df = df[available_features].copy()
        target_series = df['amount'].copy()
        
        # Drop rows where any feature is NaN (from shifts at the beginning)
        valid_mask = ~feature_df.isna().any(axis=1)
        features = feature_df[valid_mask].values
        targets = target_series[valid_mask].values
        
        # Check if we have enough data after dropping NaNs
        # Need at least 2 data points (reduced from 3 to allow predictions with limited historical data)
        if len(features) < 2:
            print(f"DEBUG: Only {len(features)} valid data points after dropping NaNs (need at least 2)", file=sys.stderr)
            return None, None
        
        print(f"DEBUG: Prepared {len(features)} valid feature rows from {len(df)} monthly data points", file=sys.stderr)
        
        return features, targets
    
    def get_model_path(self, user_id, category_id):
        """Get the file path for storing the model"""
        return os.path.join(self.model_storage_path, f'forecast_user_{user_id}_cat_{category_id}.pkl')
    
    def save_model(self, model, model_name, user_id, category_id, features, targets, performance):
        """Save trained model to disk"""
        try:
            model_path = self.get_model_path(user_id, category_id)
            
            model_data = {
                'model': model,
                'scaler': self.scaler,
                'model_name': model_name,
                'feature_names': self.get_feature_names(),
                'trained_at': datetime.now().isoformat(),
                'data_points': len(features),
                'performance': performance,
                'user_id': user_id,
                'category_id': category_id
            }
            
            joblib.dump(model_data, model_path)
            print(f"DEBUG: Model saved to {model_path}", file=sys.stderr)
            return model_path
            
        except Exception as e:
            print(f"ERROR: Failed to save model: {str(e)}", file=sys.stderr)
            return None
    
    def load_model(self, user_id, category_id):
        """Load trained model from disk"""
        try:
            model_path = self.get_model_path(user_id, category_id)
            
            if not os.path.exists(model_path):
                print(f"DEBUG: No saved model found at {model_path}", file=sys.stderr)
                return None
            
            # Check if model is fresh (less than 7 days old)
            model_age = datetime.now() - datetime.fromtimestamp(os.path.getmtime(model_path))
            if model_age.days > 7:
                print(f"DEBUG: Model is {model_age.days} days old, too stale", file=sys.stderr)
                return None
            
            model_data = joblib.load(model_path)
            print(f"DEBUG: Model loaded from {model_path}", file=sys.stderr)
            return model_data
            
        except Exception as e:
            print(f"ERROR: Failed to load model: {str(e)}", file=sys.stderr)
            return None
    
    def train_models(self, features, targets):
        """Train multiple ML models and return the best one"""
        if len(features) < 3:
            return None, None
            
        # Scale features
        features_scaled = self.scaler.fit_transform(features)
        
        best_model = None
        best_score = -float('inf')
        best_model_name = None
        trained_models = {}  # Track which models are already trained
        
        for name, model in self.models.items():
            try:
                if len(features) >= 4:
                    cv_folds = min(3, max(2, len(features) - 1))  
                    cv_scores = cross_val_score(model, features_scaled, targets, cv=cv_folds, scoring='r2')
                    avg_score = np.mean(cv_scores)
                else:
                    # For very limited data (3 samples), just train on all and use R² = 0 as baseline
                    model.fit(features_scaled, targets)
                    trained_models[name] = model  # Save trained model
                    predictions = model.predict(features_scaled)
                    # Simple R² calculation
                    ss_res = np.sum((targets - predictions) ** 2)
                    ss_tot = np.sum((targets - np.mean(targets)) ** 2)
                    avg_score = 1 - (ss_res / ss_tot) if ss_tot > 0 else 0
                
                if avg_score > best_score:
                    best_score = avg_score
                    best_model_name = name
                    # If model was already trained, use it; otherwise will train below
                    if name in trained_models:
                        best_model = trained_models[name]
                    
            except Exception as e:
                continue
        
        # Train the best model on all data for final use (unless already trained)
        if best_model is None and best_model_name:
            # Create a fresh instance of the best model and train it
            best_model = self.models[best_model_name]
            best_model.fit(features_scaled, targets)
        elif best_model is None:
            # Fallback: train first available model
            if len(self.models) > 0:
                best_model_name = list(self.models.keys())[0]
                best_model = self.models[best_model_name]
                best_model.fit(features_scaled, targets)
        
        return best_model, best_model_name
    
    def make_prediction(self, model, features, targets, target_month=None, target_year=None):
        """Make prediction for specified month or next month"""
        if model is None or len(features) < 1:
            return None
            
        try:
            # Prepare features for target month
            last_features = features[-1:].copy()
            feature_names = self.get_feature_names()
            
            # If target month/year specified, use them; otherwise predict next month
            if target_month is not None:
                prediction_month = target_month
            else:
                # Default to next month
                month_idx = feature_names.index('month') if 'month' in feature_names else None
                if month_idx is not None:
                    current_month = int(last_features[0, month_idx])
                    prediction_month = (current_month % 12) + 1
                else:
                    prediction_month = 1
            
            # Update month feature
            if 'month' in feature_names:
                month_idx = feature_names.index('month')
                last_features[0, month_idx] = prediction_month
            
            # Update time-based features: shift forward by one month
            # previous_month for prediction_month should be the last known month's amount
            if len(targets) > 0:
                last_amount = targets[-1]  # Last known month's amount (e.g., June for July prediction)
                
                if 'previous_month' in feature_names:
                    prev_month_idx = feature_names.index('previous_month')
                    last_features[0, prev_month_idx] = last_amount
                
                # previous_2months should be second-to-last known amount
                if len(targets) > 1 and 'previous_2months' in feature_names:
                    prev_2months_idx = feature_names.index('previous_2months')
                    last_features[0, prev_2months_idx] = targets[-2]
                
                # previous_3months should be third-to-last known amount
                if len(targets) > 2 and 'previous_3months' in feature_names:
                    prev_3months_idx = feature_names.index('previous_3months')
                    last_features[0, prev_3months_idx] = targets[-3]
                
                # Update rolling features based on last known values
                # rolling_3m: average of last 3 known months (if available)
                if len(targets) >= 3 and 'rolling_3m' in feature_names:
                    rolling_3m_idx = feature_names.index('rolling_3m')
                    rolling_3m_val = np.mean(targets[-3:])
                    last_features[0, rolling_3m_idx] = rolling_3m_val
                
                # rolling_6m: average of last 6 known months (if available)
                if len(targets) >= 6 and 'rolling_6m' in feature_names:
                    rolling_6m_idx = feature_names.index('rolling_6m')
                    rolling_6m_val = np.mean(targets[-6:])
                    last_features[0, rolling_6m_idx] = rolling_6m_val
                elif len(targets) >= 3 and 'rolling_6m' in feature_names:
                    # Use available data if less than 6 months
                    rolling_6m_idx = feature_names.index('rolling_6m')
                    rolling_6m_val = np.mean(targets)
                    last_features[0, rolling_6m_idx] = rolling_6m_val
                
                # rolling_12m: use available data
                if len(targets) >= 12 and 'rolling_12m' in feature_names:
                    rolling_12m_idx = feature_names.index('rolling_12m')
                    rolling_12m_val = np.mean(targets[-12:])
                    last_features[0, rolling_12m_idx] = rolling_12m_val
                elif len(targets) >= 6 and 'rolling_12m' in feature_names:
                    rolling_12m_idx = feature_names.index('rolling_12m')
                    rolling_12m_val = np.mean(targets[-6:])
                    last_features[0, rolling_12m_idx] = rolling_12m_val
                
                # rolling_std_3m and rolling_std_6m: calculate from available data
                if len(targets) >= 3 and 'rolling_std_3m' in feature_names:
                    rolling_std_3m_idx = feature_names.index('rolling_std_3m')
                    rolling_std_3m_val = np.std(targets[-3:]) if len(targets[-3:]) > 1 else 0
                    last_features[0, rolling_std_3m_idx] = rolling_std_3m_val
                
                if len(targets) >= 6 and 'rolling_std_6m' in feature_names:
                    rolling_std_6m_idx = feature_names.index('rolling_std_6m')
                    rolling_std_6m_val = np.std(targets[-6:]) if len(targets[-6:]) > 1 else 0
                    last_features[0, rolling_std_6m_idx] = rolling_std_6m_val
                
                # trend_3m: calculate slope from last 3 months
                if len(targets) >= 3 and 'trend_3m' in feature_names:
                    trend_3m_idx = feature_names.index('trend_3m')
                    x = np.arange(len(targets[-3:]))
                    y = targets[-3:]
                    if len(y) > 1:
                        slope, _ = np.polyfit(x, y, 1)
                        last_features[0, trend_3m_idx] = slope
                    else:
                        last_features[0, trend_3m_idx] = 0
                
            # Update seasonal features based on target month
            if 'is_holiday_season' in feature_names:
                holiday_idx = feature_names.index('is_holiday_season')
                last_features[0, holiday_idx] = 1 if prediction_month in [11, 12, 1] else 0
                
            if 'is_summer' in feature_names:
                summer_idx = feature_names.index('is_summer')
                last_features[0, summer_idx] = 1 if prediction_month in [6, 7, 8] else 0
            
            # Scale features
            last_features_scaled = self.scaler.transform(last_features)
            
            # Make prediction
            prediction = model.predict(last_features_scaled)[0]
            
            # Ensure prediction is non-negative
            prediction = max(0, prediction)
            
            return prediction
            
        except Exception as e:
            print(f"DEBUG: Prediction error: {str(e)}", file=sys.stderr)
            import traceback
            print(f"DEBUG: Traceback: {traceback.format_exc()}", file=sys.stderr)
            return None
    
    def get_feature_names(self):
        """Get list of feature names"""
        return [
            'month', 'day_of_week', 'previous_month', 'previous_2months', 'previous_3months',
            'rolling_3m', 'rolling_6m', 'rolling_12m', 'rolling_std_3m', 'rolling_std_6m',
            'trend_3m', 'is_holiday_season', 'is_summer', 'budget_percentage'
        ]
    
    def calculate_performance_metrics(self, model, features, targets):
        """Calculate model performance metrics using proper train-test split"""
        if model is None or len(features) < 6:
            return {
                'mae': 0,
                'mape': 0,
                'rmse': 0,
                'r2_score': 0
            }
            
        try:
            # Use time-series aware train-test split (last 20% for testing)
            split_idx = int(len(features) * 0.8)
            if split_idx < 3:  # Need at least 3 points for training
                split_idx = 3
                
            X_train, X_test = features[:split_idx], features[split_idx:]
            y_train, y_test = targets[:split_idx], targets[split_idx:]
            
            if len(X_test) < 1:  # Need at least 1 test point
                # Fallback to cross-validation
                local_scaler = StandardScaler()
                features_scaled = local_scaler.fit_transform(features)
                cv_scores = cross_val_score(model, features_scaled, targets, cv=min(3, len(features)-1), scoring='r2')
                r2 = float(np.mean(cv_scores))
                std_targets = float(np.std(targets)) if np.std(targets) > 0 else 1.0
                mae = std_targets * max(0.0, (1 - r2)) * 0.5
                rmse = std_targets * max(0.0, (1 - r2)) * 0.7
                mape = max(0.0, (1 - r2) * 100)
                return {
                    'mae': float(mae),
                    'mape': float(mape),
                    'rmse': float(rmse),
                    'r2_score': float(r2)
                }
            
            # Fit scaler on training data only
            local_scaler = StandardScaler()
            X_train_scaled = local_scaler.fit_transform(X_train)
            X_test_scaled = local_scaler.transform(X_test)
            
            # Train model on training data
            model.fit(X_train_scaled, y_train)
            
            # Make predictions on test data
            predictions = model.predict(X_test_scaled)
            
            # Calculate metrics on test data (not training data!)
            mae = mean_absolute_error(y_test, predictions)
            rmse = np.sqrt(mean_squared_error(y_test, predictions))
            r2 = r2_score(y_test, predictions)
            
            # Calculate MAPE
            mape = np.mean(np.abs((y_test - predictions) / np.maximum(y_test, 1))) * 100
            
            return {
                'mae': float(mae),
                'mape': float(mape),
                'rmse': float(rmse),
                'r2_score': float(r2)
            }
            
        except Exception:
            return {
                'mae': 0,
                'mape': 0,
                'rmse': 0,
                'r2_score': 0
            }
    
    def forecast(self, user_id, category_id, force_retrain=False, target_month=None, target_year=None):
        """Main forecasting method with model persistence"""
        try:
            # Try to load existing model first (unless forced to retrain)
            if not force_retrain:
                saved_model_data = self.load_model(user_id, category_id)
                if saved_model_data:
                    print("DEBUG: Using saved model for prediction", file=sys.stderr)
                    
                    # Get fresh data for prediction (filtered up to target month if specified)
                    df = self.get_user_data(user_id, category_id, target_month, target_year)
                    if df is None or len(df) == 0:
                        # If no filtered data, try using all data (if target month allows)
                        df = self.get_user_data(user_id, category_id, None, None)
                        if df is None or len(df) == 0:
                            return {'error': 'No data available for forecasting'}
                    
                    # Prepare features with saved scaler
                    features, targets = self.prepare_features(df)
                    if features is None or len(features) < 1:
                        # Return error - let the controller handle statistical fallback
                        return {'error': 'Insufficient data for feature preparation'}
                    
                    # Use saved model and scaler
                    model = saved_model_data['model']
                    self.scaler = saved_model_data['scaler']
                    
                    # Make prediction with saved model
                    prediction = self.make_prediction(model, features, targets, target_month, target_year)
                    if prediction is not None:
                        return {
                            'prediction': float(prediction),
                            'accuracy': float(saved_model_data['performance']['r2_score']),
                            'model_type': saved_model_data['model_name'],
                            'data_points': int(saved_model_data['data_points']),
                            'performance': saved_model_data['performance'],
                            'method': 'Machine Learning (Cached)',
                            'model_age_days': (datetime.now() - datetime.fromisoformat(saved_model_data['trained_at'])).days,
                            'target_month': target_month,
                            'target_year': target_year
                        }
                    # If prediction failed, fall through to train fresh model
            
            print("DEBUG: Training fresh model", file=sys.stderr)
            
            # For training, use ALL available data (no date filter) to ensure we have enough data points
            # This ensures the model learns from all historical patterns
            df = self.get_user_data(user_id, category_id, None, None)
            if df is None:
                return {'error': 'No data available for forecasting'}
            
            # Prepare features
            features, targets = self.prepare_features(df)
            if features is None:
                return {'error': 'Insufficient data for feature preparation'}
            
            # Train models (need at least 3 valid feature rows)
            best_model, best_model_name = self.train_models(features, targets)
            if best_model is None:
                # Return error - let the controller handle statistical fallback
                return {'error': 'Failed to train any models'}
            
            # Calculate performance metrics
            performance = self.calculate_performance_metrics(best_model, features, targets)
            
            # Save the trained model
            model_path = self.save_model(best_model, best_model_name, user_id, category_id, features, targets, performance)
            
            # For prediction, use filtered data up to target month (if specified)
            # This ensures we don't use future data in the prediction
            if target_month and target_year:
                df_pred = self.get_user_data(user_id, category_id, target_month, target_year)
                if df_pred is not None:
                    features_pred, targets_pred = self.prepare_features(df_pred)
                    if features_pred is not None and len(features_pred) >= 1:
                        features, targets = features_pred, targets_pred
            
            # Make prediction
            prediction = self.make_prediction(best_model, features, targets, target_month, target_year)
            if prediction is None:
                return {'error': 'Failed to make prediction'}
            
            return {
                'prediction': float(prediction),
                'accuracy': float(performance['r2_score']),
                'model_type': best_model_name,
                'data_points': int(len(features)),
                'performance': performance,
                'method': 'Machine Learning (Fresh)',
                'model_path': model_path,
                'target_month': target_month,
                'target_year': target_year
            }
            
        except Exception as e:
            return {'error': f'Forecasting error: {str(e)}'}

def main():
    parser = argparse.ArgumentParser(description='Expense Forecasting ML Script')
    parser.add_argument('--user-id', required=True, type=int, help='User ID')
    parser.add_argument('--category-id', required=True, type=int, help='Category ID')
    parser.add_argument('--db-path', help='SQLite database path')
    parser.add_argument('--db-type', choices=['sqlite', 'mysql'], default='sqlite', help='Database type')
    parser.add_argument('--db-host', help='MySQL host')
    parser.add_argument('--db-port', help='MySQL port')
    parser.add_argument('--db-name', help='MySQL database name')
    parser.add_argument('--db-user', help='MySQL username')
    parser.add_argument('--db-password', help='MySQL password')
    parser.add_argument('--target-month', type=int, help='Target month (1-12) for prediction')
    parser.add_argument('--target-year', type=int, help='Target year for prediction')
    parser.add_argument('--performance-only', action='store_true', help='Only return performance metrics')
    parser.add_argument('--aggregate-monthly', action='store_true', help='Aggregate to monthly totals (performance only)')
    parser.add_argument('--force-retrain', action='store_true', help='Force retraining even if saved model exists')
    parser.add_argument('--model-storage-path', help='Path to store ML models')
    
    args = parser.parse_args()
    
    # Initialize forecaster based on database type
    if args.db_type == 'mysql':
        if not all([args.db_host, args.db_name, args.db_user]):
            print(json.dumps({'error': 'MySQL requires host, database name, and username'}))
            return
            
        db_config = {
            'host': args.db_host,
            'port': args.db_port or 3306,
            'database': args.db_name,
            'username': args.db_user,
            'password': args.db_password or ''
        }
        forecaster = ExpenseForecaster(db_config=db_config, model_storage_path=args.model_storage_path)
    else:
        if not args.db_path:
            print(json.dumps({'error': 'SQLite requires database path'}))
            return
        forecaster = ExpenseForecaster(db_path=args.db_path, model_storage_path=args.model_storage_path)
    
    if args.performance_only:
        # Get performance metrics only
        df = forecaster.get_user_data(args.user_id, args.category_id)
        if df is not None:
            # Optional monthly aggregation for evaluation only
            if args.aggregate_monthly:
                df['date'] = pd.to_datetime(df['date'])
                df['month_key'] = df['date'].dt.to_period('M')
                df = df.groupby('month_key', as_index=False)['amount'].sum()
                # rebuild a date column as month start for feature prep
                df['date'] = df['month_key'].dt.to_timestamp()
                df = df[['date', 'amount']].sort_values('date')
            
            features, targets = forecaster.prepare_features(df)
            if features is not None:
                best_model, _ = forecaster.train_models(features, targets)
                performance = forecaster.calculate_performance_metrics(best_model, features, targets)
                print(json.dumps(performance))
            else:
                # Fallback: simple lag-1 feature evaluation on aggregated (or raw) series
                try:
                    series = df[['date','amount']].sort_values('date').copy()
                    series['lag1'] = series['amount'].shift(1)
                    series = series.dropna()
                    if len(series) < 3:
                        print(json.dumps({'mae': 0, 'mape': 0, 'rmse': 0, 'r2_score': 0}))
                    else:
                        X = series[['lag1']].values
                        y = series['amount'].values
                        # time-aware split
                        split_idx = int(len(X) * 0.8)
                        if split_idx < 2:
                            split_idx = 2
                        X_train, X_test = X[:split_idx], X[split_idx:]
                        y_train, y_test = y[:split_idx], y[split_idx:]
                        from sklearn.linear_model import LinearRegression
                        from sklearn.preprocessing import StandardScaler
                        scaler = StandardScaler()
                        X_train_scaled = scaler.fit_transform(X_train)
                        X_test_scaled = scaler.transform(X_test)
                        model = LinearRegression()
                        model.fit(X_train_scaled, y_train)
                        preds = model.predict(X_test_scaled)
                        mae = mean_absolute_error(y_test, preds)
                        rmse = np.sqrt(mean_squared_error(y_test, preds))
                        r2 = r2_score(y_test, preds) if len(y_test) > 1 else 0.0
                        mape = float(np.mean(np.abs((y_test - preds) / np.maximum(y_test, 1))) * 100)
                        print(json.dumps({'mae': float(mae), 'mape': float(mape), 'rmse': float(rmse), 'r2_score': float(r2)}))
                except Exception:
                    print(json.dumps({'mae': 0, 'mape': 0, 'rmse': 0, 'r2_score': 0}))
        else:
            print(json.dumps({'error': 'No data available'}))
    else:
        # Get full forecast (no aggregation to keep forecast value unchanged)
        result = forecaster.forecast(args.user_id, args.category_id, force_retrain=args.force_retrain, 
                                   target_month=args.target_month, target_year=args.target_year)
        print(json.dumps(result))

if __name__ == "__main__":
    main() 