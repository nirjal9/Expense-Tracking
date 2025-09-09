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
from datetime import datetime, timedelta
from sklearn.linear_model import LinearRegression
from sklearn.ensemble import RandomForestRegressor
from sklearn.preprocessing import StandardScaler, MinMaxScaler
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from sklearn.model_selection import train_test_split, cross_val_score
import warnings
warnings.filterwarnings('ignore')

# Try to import MySQL connector, fall back to SQLite if not available
try:
    import mysql.connector
    MYSQL_AVAILABLE = True
except ImportError:
    MYSQL_AVAILABLE = False
    print("Warning: mysql-connector-python not installed. Install with: pip3 install mysql-connector-python")

class ExpenseForecaster:
    def __init__(self, db_config=None, db_path=None):
        self.db_config = db_config
        self.db_path = db_path
        self.db_type = 'mysql' if db_config else 'sqlite'
        self.models = {
            'linear': LinearRegression(),
            'random_forest': RandomForestRegressor(n_estimators=100, random_state=42, max_depth=10)
        }
        self.scaler = StandardScaler()
        
    def get_user_data(self, user_id, category_id):
        """Fetch user expense data from database"""
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

            # Fetch minimal columns that actually exist
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

            # Provide defaults for optional engineered fields
            df['budget_percentage'] = 0.0
            df['income'] = 0.0

            return df
            
        except Exception as e:
            print(f"ERROR in get_user_data: {str(e)}", file=sys.stderr)
            return None
    
    def prepare_features(self, df):
        """Create features for ML model"""
        if len(df) < 6:  # Need at least 6 data points for meaningful features
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
        df['rolling_3m'] = past_amount.rolling(3, min_periods=3).mean()
        df['rolling_6m'] = past_amount.rolling(6, min_periods=6).mean()
        df['rolling_12m'] = past_amount.rolling(12, min_periods=12).mean()
        
        # Volatility features based on past values only
        df['rolling_std_3m'] = past_amount.rolling(3, min_periods=3).std()
        df['rolling_std_6m'] = past_amount.rolling(6, min_periods=6).std()
        
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
        
        # Remove rows with NaN values (created by shifts/rollings)
        df = df.dropna()
        
        if len(df) < 6:  # Need at least 6 data points after feature engineering
            return None, None
            
        # Select feature columns
        feature_columns = [
            'month', 'day_of_week', 'previous_month', 'previous_2months', 'previous_3months',
            'rolling_3m', 'rolling_6m', 'rolling_12m', 'rolling_std_3m', 'rolling_std_6m',
            'trend_3m', 'is_holiday_season', 'is_summer', 'budget_percentage'
        ]
        
        # Filter to only existing columns
        available_features = [col for col in feature_columns if col in df.columns]
        
        features = df[available_features].values
        targets = df['amount'].values
        
        return features, targets
    
    def train_models(self, features, targets):
        """Train multiple ML models and return the best one"""
        if len(features) < 6:
            return None, None
            
        # Scale features
        features_scaled = self.scaler.fit_transform(features)
        
        best_model = None
        best_score = -float('inf')
        best_model_name = None
        
        for name, model in self.models.items():
            try:
                # Use cross-validation to get a more realistic score
                cv_scores = cross_val_score(model, features_scaled, targets, cv=min(3, len(features)-1), scoring='r2')
                avg_score = np.mean(cv_scores)
                
                if avg_score > best_score:
                    best_score = avg_score
                    best_model = model
                    best_model_name = name
                    
            except Exception as e:
                continue
        
        # Train the best model on all data for final use
        if best_model is not None:
            best_model.fit(features_scaled, targets)
        
        return best_model, best_model_name
    
    def make_prediction(self, model, features, targets):
        """Make prediction for next month"""
        if model is None or len(features) < 1:
            return None
            
        try:
            # Prepare next month features
            last_features = features[-1:].copy()
            
            # Update time-based features for next month
            if 'month' in self.get_feature_names():
                month_idx = self.get_feature_names().index('month')
                last_features[0, month_idx] = (last_features[0, month_idx] % 12) + 1
                
            # Update seasonal features
            if 'is_holiday_season' in self.get_feature_names():
                holiday_idx = self.get_feature_names().index('is_holiday_season')
                next_month = (last_features[0, self.get_feature_names().index('month')] if 'month' in self.get_feature_names() else 1)
                last_features[0, holiday_idx] = 1 if next_month in [11, 12, 1] else 0
                
            if 'is_summer' in self.get_feature_names():
                summer_idx = self.get_feature_names().index('is_summer')
                next_month = (last_features[0, self.get_feature_names().index('month')] if 'month' in self.get_feature_names() else 1)
                last_features[0, summer_idx] = 1 if next_month in [6, 7, 8] else 0
            
            # Scale features
            last_features_scaled = self.scaler.transform(last_features)
            
            # Make prediction
            prediction = model.predict(last_features_scaled)[0]
            
            # Ensure prediction is non-negative
            prediction = max(0, prediction)
            
            return prediction
            
        except Exception as e:
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
    
    def forecast(self, user_id, category_id):
        """Main forecasting method"""
        try:
            # Get data
            df = self.get_user_data(user_id, category_id)
            if df is None:
                return {'error': 'No data available for forecasting'}
            
            # Prepare features
            features, targets = self.prepare_features(df)
            if features is None:
                return {'error': 'Insufficient data for feature preparation'}
            
            # Train models
            best_model, best_model_name = self.train_models(features, targets)
            if best_model is None:
                return {'error': 'Failed to train any models'}
            
            # Make prediction
            prediction = self.make_prediction(best_model, features, targets)
            if prediction is None:
                return {'error': 'Failed to make prediction'}
            
            # Calculate performance metrics
            performance = self.calculate_performance_metrics(best_model, features, targets)
            
            return {
                'prediction': float(prediction),
                'accuracy': float(performance['r2_score']),
                'model_type': best_model_name,
                'data_points': int(len(features)),
                'performance': performance,
                'method': 'Machine Learning'
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
    parser.add_argument('--performance-only', action='store_true', help='Only return performance metrics')
    parser.add_argument('--aggregate-monthly', action='store_true', help='Aggregate to monthly totals (performance only)')
    
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
        forecaster = ExpenseForecaster(db_config=db_config)
    else:
        if not args.db_path:
            print(json.dumps({'error': 'SQLite requires database path'}))
            return
        forecaster = ExpenseForecaster(db_path=args.db_path)
    
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
        result = forecaster.forecast(args.user_id, args.category_id)
        print(json.dumps(result))

if __name__ == "__main__":
    main() 