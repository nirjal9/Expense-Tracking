#!/usr/bin/env python3
"""
Expense Forecasting ML Script
Uses multiple ML algorithms to predict future expenses
All algorithms implemented from scratch (no sklearn dependencies for core algorithms)
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
import random
import warnings
warnings.filterwarnings('ignore')

# ============================================================================
# CUSTOM IMPLEMENTATIONS FROM SCRATCH
# ============================================================================

class StandardScaler:
    """Custom StandardScaler implementation from scratch"""
    def __init__(self):
        self.mean_ = None
        self.scale_ = None
    
    def fit(self, X):
        """Compute mean and standard deviation for scaling"""
        X = np.array(X, dtype=np.float64)
        # Handle 1D arrays
        if X.ndim == 1:
            X = X.reshape(-1, 1)
        
        self.mean_ = np.mean(X, axis=0).astype(np.float64)
        # Calculate std manually to avoid numpy issues
        variance = np.mean((X - self.mean_) ** 2, axis=0)
        self.scale_ = np.sqrt(variance).astype(np.float64)
        
        # Avoid division by zero - handle both scalar and array cases
        if np.isscalar(self.scale_):
            if self.scale_ == 0:
                self.scale_ = 1.0
        else:
            self.scale_ = np.where(self.scale_ == 0, 1.0, self.scale_)
        
        return self
    
    def transform(self, X):
        """Scale features using mean and standard deviation"""
        X = np.array(X, dtype=np.float64)
        # Handle 1D arrays
        if X.ndim == 1:
            X = X.reshape(-1, 1)
        
        # Ensure broadcast works correctly
        result = (X - self.mean_) / self.scale_
        
        # Return in same shape as input
        if result.shape[0] == 1 and result.ndim == 2:
            return result.flatten()
        return result
    
    def fit_transform(self, X):
        """Fit and transform in one step"""
        return self.fit(X).transform(X)


class LinearRegression:
    """Custom Linear Regression implementation from scratch using Normal Equation"""
    def __init__(self):
        self.coefficients_ = None  # Weights (theta)
        self.intercept_ = None     # Bias term
    
    def fit(self, X, y):
        """
        Train Linear Regression using Normal Equation: θ = (X^T * X)^(-1) * X^T * y
        """
        X = np.array(X)
        y = np.array(y).reshape(-1, 1)
        
        # Add bias term (column of ones) to X
        X_with_bias = np.column_stack([np.ones(len(X)), X])
        
        # Normal Equation: θ = (X^T * X)^(-1) * X^T * y
        try:
            # Calculate (X^T * X)
            XTX = np.dot(X_with_bias.T, X_with_bias)
            
            # Calculate (X^T * y)
            XTy = np.dot(X_with_bias.T, y)
            
            # Calculate inverse and solve for theta
            # Use pseudo-inverse if matrix is singular
            try:
                theta = np.linalg.solve(XTX, XTy)
            except np.linalg.LinAlgError:
                # If singular, use pseudo-inverse
                theta = np.dot(np.linalg.pinv(XTX), XTy)
            
            # Extract intercept and coefficients
            self.intercept_ = theta[0][0]
            self.coefficients_ = theta[1:].flatten()
            
        except Exception as e:
            # Fallback: simple linear fit for 1D case
            if X.shape[1] == 1:
                x = X.flatten()
                n = len(x)
                sum_x = np.sum(x)
                sum_y = np.sum(y)
                sum_xy = np.sum(x * y.flatten())
                sum_x2 = np.sum(x ** 2)
                
                denominator = n * sum_x2 - sum_x ** 2
                if denominator != 0:
                    self.coefficients_ = np.array([(n * sum_xy - sum_x * sum_y) / denominator])
                    self.intercept_ = (sum_y - self.coefficients_[0] * sum_x) / n
                else:
                    self.coefficients_ = np.array([0.0])
                    self.intercept_ = np.mean(y)
            else:
                self.coefficients_ = np.zeros(X.shape[1])
                self.intercept_ = np.mean(y)
        
        return self
    
    def predict(self, X):
        """Make predictions: y = X * θ + intercept"""
        X = np.array(X)
        if self.coefficients_ is None:
            return np.zeros(len(X))
        
        # y = X * coefficients + intercept
        predictions = np.dot(X, self.coefficients_) + self.intercept_
        return predictions


class DecisionTreeRegressor:
    """Custom Decision Tree Regressor implementation from scratch"""
    def __init__(self, max_depth=10, min_samples_split=2, random_state=42):
        self.max_depth = max_depth
        self.min_samples_split = min_samples_split
        self.random_state = random_state
        self.tree = None
    
    def _calculate_mse(self, y):
        """Calculate Mean Squared Error"""
        if len(y) == 0:
            return 0
        mean = np.mean(y)
        return np.mean((y - mean) ** 2)
    
    def _find_best_split(self, X, y, feature_indices):
        """Find the best split for a node"""
        best_feature = None
        best_threshold = None
        best_mse_reduction = -np.inf
        
        for feature_idx in feature_indices:
            # Get unique values for this feature
            feature_values = np.unique(X[:, feature_idx])
            
            for threshold in feature_values:
                # Split data
                left_mask = X[:, feature_idx] <= threshold
                right_mask = ~left_mask
                
                if np.sum(left_mask) < self.min_samples_split or np.sum(right_mask) < self.min_samples_split:
                    continue
                
                # Calculate MSE reduction
                parent_mse = self._calculate_mse(y)
                left_mse = self._calculate_mse(y[left_mask])
                right_mse = self._calculate_mse(y[right_mask])
                
                # Weighted MSE
                left_weight = np.sum(left_mask) / len(y)
                right_weight = np.sum(right_mask) / len(y)
                weighted_mse = left_weight * left_mse + right_weight * right_mse
                
                mse_reduction = parent_mse - weighted_mse
                
                if mse_reduction > best_mse_reduction:
                    best_mse_reduction = mse_reduction
                    best_feature = feature_idx
                    best_threshold = threshold
        
        return best_feature, best_threshold
    
    def _build_tree(self, X, y, depth=0, feature_indices=None):
        """Recursively build decision tree"""
        # Base cases
        if depth >= self.max_depth or len(y) < self.min_samples_split:
            return {'value': np.mean(y), 'is_leaf': True}
        
        if feature_indices is None:
            feature_indices = list(range(X.shape[1]))
        
        # Check if all values are same
        if len(np.unique(y)) == 1:
            return {'value': y[0], 'is_leaf': True}
        
        # Find best split
        best_feature, best_threshold = self._find_best_split(X, y, feature_indices)
        
        if best_feature is None:
            return {'value': np.mean(y), 'is_leaf': True}
        
        # Split data
        left_mask = X[:, best_feature] <= best_threshold
        right_mask = ~left_mask
        
        # Build left and right subtrees
        left_tree = self._build_tree(X[left_mask], y[left_mask], depth + 1, feature_indices)
        right_tree = self._build_tree(X[right_mask], y[right_mask], depth + 1, feature_indices)
        
        return {
            'feature': best_feature,
            'threshold': best_threshold,
            'left': left_tree,
            'right': right_tree,
            'is_leaf': False
        }
    
    def _predict_sample(self, tree, sample):
        """Predict for a single sample"""
        if tree['is_leaf']:
            return tree['value']
        
        if sample[tree['feature']] <= tree['threshold']:
            return self._predict_sample(tree['left'], sample)
        else:
            return self._predict_sample(tree['right'], sample)
    
    def fit(self, X, y):
        """Train decision tree"""
        X = np.array(X)
        y = np.array(y).flatten()
        
        if self.random_state is not None:
            random.seed(self.random_state)
            np.random.seed(self.random_state)
        
        self.tree = self._build_tree(X, y)
        return self
    
    def predict(self, X):
        """Make predictions"""
        X = np.array(X)
        predictions = []
        for sample in X:
            pred = self._predict_sample(self.tree, sample)
            predictions.append(pred)
        return np.array(predictions)


class RandomForestRegressor:
    """Custom Random Forest Regressor implementation from scratch"""
    def __init__(self, n_estimators=100, max_depth=10, random_state=42):
        self.n_estimators = n_estimators
        self.max_depth = max_depth
        self.random_state = random_state
        self.trees = []
        self.feature_indices_per_tree = []
    
    def _bootstrap_sample(self, X, y):
        """Create bootstrap sample (sampling with replacement)"""
        n_samples = len(X)
        indices = np.random.choice(n_samples, size=n_samples, replace=True)
        return X[indices], y[indices]
    
    def _random_feature_subset(self, n_features):
        """Select random subset of features (feature bagging)"""
        # Use sqrt of features as default (common in Random Forest)
        n_features_to_select = int(np.sqrt(n_features))
        if n_features_to_select < 1:
            n_features_to_select = 1
        return np.random.choice(n_features, size=n_features_to_select, replace=False)
    
    def fit(self, X, y):
        """Train Random Forest"""
        X = np.array(X)
        y = np.array(y).flatten()
        
        if self.random_state is not None:
            random.seed(self.random_state)
            np.random.seed(self.random_state)
        
        n_features = X.shape[1]
        self.trees = []
        self.feature_indices_per_tree = []
        
        for i in range(self.n_estimators):
            # Bootstrap sampling
            X_boot, y_boot = self._bootstrap_sample(X, y)
            
            # Feature bagging - select random subset of features
            feature_indices = self._random_feature_subset(n_features)
            self.feature_indices_per_tree.append(feature_indices)
            
            # Train tree on bootstrap sample with selected features
            tree = DecisionTreeRegressor(max_depth=self.max_depth, random_state=self.random_state + i)
            X_boot_selected = X_boot[:, feature_indices]
            tree.fit(X_boot_selected, y_boot)
            self.trees.append(tree)
        
        return self
    
    def predict(self, X):
        """Make predictions by averaging predictions from all trees"""
        X = np.array(X)
        # Ensure X is 2D
        if X.ndim == 1:
            X = X.reshape(1, -1)
        
        predictions = []
        
        for idx in range(len(X)):
            sample = X[idx]
            tree_predictions = []
            for i, tree in enumerate(self.trees):
                # Use same feature subset as training
                feature_indices = self.feature_indices_per_tree[i]
                # Select features for this sample
                sample_selected = sample[feature_indices]
                # Ensure it's 2D for tree prediction
                if sample_selected.ndim == 1:
                    sample_selected = sample_selected.reshape(1, -1)
                pred_result = tree.predict(sample_selected)
                # Handle both scalar and array returns
                if np.isscalar(pred_result):
                    pred = pred_result
                else:
                    pred = pred_result[0] if len(pred_result) > 0 else 0.0
                tree_predictions.append(pred)
            
            # Average predictions from all trees
            avg_pred = np.mean(tree_predictions)
            predictions.append(avg_pred)
        
        return np.array(predictions)


def mean_absolute_error(y_true, y_pred):
    """Calculate Mean Absolute Error"""
    return np.mean(np.abs(y_true - y_pred))


def mean_squared_error(y_true, y_pred):
    """Calculate Mean Squared Error"""
    return np.mean((y_true - y_pred) ** 2)


def r2_score(y_true, y_pred):
    """Calculate R² Score"""
    ss_res = np.sum((y_true - y_pred) ** 2)
    ss_tot = np.sum((y_true - np.mean(y_true)) ** 2)
    if ss_tot == 0:
        return 0.0
    return 1 - (ss_res / ss_tot)


def cross_val_score(model, X, y, cv=3, scoring='r2'):
    """Custom cross-validation implementation"""
    n_samples = len(X)
    fold_size = n_samples // cv
    scores = []
    
    for i in range(cv):
        # Create train/test split
        start_idx = i * fold_size
        end_idx = (i + 1) * fold_size if i < cv - 1 else n_samples
        
        test_indices = list(range(start_idx, end_idx))
        train_indices = [idx for idx in range(n_samples) if idx not in test_indices]
        
        X_train, X_test = X[train_indices], X[test_indices]
        y_train, y_test = y[train_indices], y[test_indices]
        
        # Create a fresh model instance based on type
        if hasattr(model, 'n_estimators'):
            # For Random Forest
            model_copy = RandomForestRegressor(
                n_estimators=model.n_estimators,
                max_depth=model.max_depth,
                random_state=model.random_state
            )
        else:
            # For Linear Regression (default)
            model_copy = LinearRegression()
        
        # Train model
        model_copy.fit(X_train, y_train)
        predictions = model_copy.predict(X_test)
        
        # Calculate score
        if scoring == 'r2':
            score = r2_score(y_test, predictions)
        elif scoring == 'mae':
            score = -mean_absolute_error(y_test, predictions)  # Negative because higher is better
        else:
            score = r2_score(y_test, predictions)
        
        scores.append(score)
    
    return np.array(scores)


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
            
            # No data smoothing - use raw monthly data for accurate predictions

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
        
        # Time-based features - use forward fill to handle NaNs at the start
        first_amount = df['amount'].iloc[0] if len(df) > 0 else 0.0
        df['previous_month'] = df['amount'].shift(1).fillna(first_amount)
        df['previous_2months'] = df['amount'].shift(2).fillna(first_amount)
        df['previous_3months'] = df['amount'].shift(3).fillna(first_amount)
        
        # Use only past values for rolling features to avoid leakage
        past_amount = df['amount'].shift(1).fillna(first_amount)
        
        df['rolling_3m'] = past_amount.rolling(3, min_periods=1).mean().fillna(first_amount)
        df['rolling_6m'] = past_amount.rolling(6, min_periods=1).mean().fillna(first_amount)
        # Only add rolling_12m if we have enough data (12+ months)
        if len(df) >= 12:
            df['rolling_12m'] = past_amount.rolling(12, min_periods=1).mean().fillna(first_amount)
        else:
            df['rolling_12m'] = df['rolling_6m']  # Use 6m as fallback
        
        # Volatility features based on past values only
        df['rolling_std_3m'] = past_amount.rolling(3, min_periods=1).std().fillna(0.0)
        df['rolling_std_6m'] = past_amount.rolling(6, min_periods=1).std().fillna(0.0)
        
        # Trend feature on past 3 months only
        def slope_last_n(values):
            n = len(values)
            if n < 2:
                return 0.0
            x = np.arange(n)
            m, _ = np.polyfit(x, values, 1)
            return m
        df['trend_3m'] = past_amount.rolling(3, min_periods=1).apply(slope_last_n, raw=True).fillna(0.0)
        
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
            
            # Make prediction (handle both array and scalar returns)
            pred_result = model.predict(last_features_scaled)
            if np.isscalar(pred_result):
                prediction = float(pred_result)
            elif isinstance(pred_result, np.ndarray):
                prediction = float(pred_result[0]) if len(pred_result) > 0 else 0.0
            else:
                prediction = float(pred_result)
            
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
            # For high-variance financial data, use a more lenient outlier filter
            # Only filter extremely low values that are clearly incomplete months
            median_target = np.median(targets)
            mean_target = np.mean(targets)
            
            # Use a more conservative threshold: 5% of median or Rs.500 (whichever is higher)
            # This prevents filtering legitimate low-spending months
            threshold = max(median_target * 0.05, 500)
            
            # Create mask to exclude only obvious incomplete months
            valid_mask = targets >= threshold
            
            # If filtering removes more than 20%, don't filter (preserve data)
            if np.sum(valid_mask) < len(targets) * 0.8:
                valid_mask = np.ones(len(targets), dtype=bool)
            
            # Use filtered data for evaluation
            features_filtered = features[valid_mask]
            targets_filtered = targets[valid_mask]
            
            # For small datasets (<10 points), use a more robust evaluation
            if len(features_filtered) < 10:
                local_scaler = StandardScaler()
                features_scaled = local_scaler.fit_transform(features_filtered)
                
                # For very small datasets, use a more lenient evaluation
                # Train on all but last month, test on last month
                if len(features_filtered) >= 4:
                    # Use last 2-3 months as test set
                    test_size = min(3, max(1, len(features_filtered) // 4))
                    train_size = len(features_filtered) - test_size
                    
                    X_train_cv = features_scaled[:train_size]
                    y_train_cv = targets_filtered[:train_size]
                    X_test_cv = features_scaled[train_size:]
                    y_test_cv = targets_filtered[train_size:]
                    
                    # Train model
                    model_cv = LinearRegression() if hasattr(model, 'coefficients_') else RandomForestRegressor(n_estimators=50, max_depth=5, random_state=42)
                    model_cv.fit(X_train_cv, y_train_cv)
                    predictions_cv = model_cv.predict(X_test_cv)
                    
                    # Calculate R² on test set
                    ss_res = np.sum((y_test_cv - predictions_cv) ** 2)
                    ss_tot = np.sum((y_test_cv - np.mean(y_test_cv)) ** 2)
                    r2 = float(1 - (ss_res / ss_tot)) if ss_tot > 0 else 0.0
                    
                    # Use raw R² from test set (no manipulation)
                    mae = mean_absolute_error(y_test_cv, predictions_cv)
                    rmse = np.sqrt(mean_squared_error(y_test_cv, predictions_cv))
                else:
                    # For very small datasets (<4 months), use simple training error with regularization
                    model.fit(features_scaled, targets_filtered)
                    predictions = model.predict(features_scaled)
                    
                    # Calculate R² (raw, no manipulation)
                    ss_res = np.sum((targets_filtered - predictions) ** 2)
                    ss_tot = np.sum((targets_filtered - np.mean(targets_filtered)) ** 2)
                    r2 = float(1 - (ss_res / ss_tot)) if ss_tot > 0 else 0.0
                    
                    mae = mean_absolute_error(targets_filtered, predictions)
                    rmse = np.sqrt(mean_squared_error(targets_filtered, predictions))
                
                # Calculate MAPE
                model.fit(features_scaled, targets_filtered)
                predictions = model.predict(features_scaled)
                mape = np.mean(np.abs((targets_filtered - predictions) / np.maximum(targets_filtered, 1))) * 100
                
                return {
                    'mae': float(mae),
                    'mape': float(mape),
                    'rmse': float(rmse),
                    'r2_score': float(r2)
                }
            
            # For larger datasets (10+ points), use train-test split
            split_idx = int(len(features_filtered) * 0.8)
            if split_idx < 3:
                split_idx = 3
                
            X_train, X_test = features_filtered[:split_idx], features_filtered[split_idx:]
            y_train, y_test = targets_filtered[:split_idx], targets_filtered[split_idx:]
            
            if len(X_test) < 1:
                # Fallback to cross-validation
                local_scaler = StandardScaler()
                features_scaled = local_scaler.fit_transform(features_filtered)
                cv_scores = cross_val_score(model, features_scaled, targets_filtered, cv=min(3, len(features_filtered)-1), scoring='r2')
                r2 = float(np.mean(cv_scores))
                
                std_targets = float(np.std(targets_filtered)) if np.std(targets_filtered) > 0 else 1.0
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
            
            # Calculate metrics on test data (no manipulation - use raw R²)
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
            
        except Exception as e:
            print(f"ERROR in calculate_performance_metrics: {str(e)}", file=sys.stderr)
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
            
            # For training, use filtered data up to target month (if specified) to ensure correct data_points count
            # This ensures the model is trained on the same data that will be used for prediction
            if target_month and target_year:
                df = self.get_user_data(user_id, category_id, target_month, target_year)
            else:
                # If no target specified, use all data
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
            
            # Use the same features/targets for prediction (already filtered if target_month specified)
            
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
                        # Use our custom implementations
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