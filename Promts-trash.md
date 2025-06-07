import cv2
import numpy as np
import tensorflow as tf
import mediapipe as mp
from ultralytics import YOLO
import torch
import asyncio
import threading
import time
from collections import deque
from typing import List, Dict, Tuple, Optional
import json

class HandDetector:
    """Stage 1: Hand Detection using MediaPipe - Real-time optimized"""
    
    def __init__(self):
        self.mp_hands = mp.solutions.hands
        self.hands = self.mp_hands.Hands(
            static_image_mode=False,
            max_num_hands=2,
            min_detection_confidence=0.7,
            min_tracking_confidence=0.5,
            model_complexity=0  # 0 = fastest, 1 = balanced
        )
        self.mp_draw = mp.solutions.drawing_utils
        
    def detect_hands(self, frame):
        """Detect hands in frame"""
        rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        results = self.hands.process(rgb_frame)
        
        hands_data = []
        if results.multi_hand_landmarks:
            for idx, hand_landmarks in enumerate(results.multi_hand_landmarks):
                # Get hand bounding box
                h, w, _ = frame.shape
                x_coords = [lm.x * w for lm in hand_landmarks.landmark]
                y_coords = [lm.y * h for lm in hand_landmarks.landmark]
                
                bbox = {
                    'x1': int(min(x_coords)),
                    'y1': int(min(y_coords)),
                    'x2': int(max(x_coords)),
                    'y2': int(max(y_coords))
                }
                
                # Expand bbox để capture object trong tay
                margin = 50
                bbox['x1'] = max(0, bbox['x1'] - margin)
                bbox['y1'] = max(0, bbox['y1'] - margin)
                bbox['x2'] = min(w, bbox['x2'] + margin)
                bbox['y2'] = min(h, bbox['y2'] + margin)
                
                hands_data.append({
                    'bbox': bbox,
                    'landmarks': hand_landmarks,
                    'hand_type': results.multi_handedness[idx].classification[0].label,
                    'confidence': results.multi_handedness[idx].classification[0].score
                })
        
        return hands_data

    def draw_hands(self, frame, hands_data):
        """Draw hand detection results"""
        for hand in hands_data:
            bbox = hand['bbox']
            # Draw hand bounding box
            cv2.rectangle(frame, (bbox['x1'], bbox['y1']), 
                         (bbox['x2'], bbox['y2']), (0, 255, 0), 2)
            
            # Draw hand type and confidence
            label = f"{hand['hand_type']} ({hand['confidence']:.2f})"
            cv2.putText(frame, label, (bbox['x1'], bbox['y1'] - 10),
                       cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 0), 2)
        
        return frame

class ObjectDetector:
    """Stage 2: Object Detection in hand region using YOLOv8"""
    
    def __init__(self):
        # Sử dụng YOLOv8 nano - nhanh nhất
        self.model = YOLO('yolov8n.pt')
        
        # Các classes quan tâm (có thể customize)
        self.target_classes = [
            'bottle', 'cup', 'fork', 'knife', 'spoon', 'bowl',
            'banana', 'apple', 'sandwich', 'orange', 'broccoli',
            'carrot', 'cell phone', 'book', 'scissors', 'teddy bear'
        ]
        
    def detect_objects_in_hands(self, frame, hands_data):
        """Detect objects trong vùng tay"""
        detected_objects = []
        
        for hand in hands_data:
            bbox = hand['bbox']
            
            # Crop hand region
            hand_region = frame[bbox['y1']:bbox['y2'], bbox['x1']:bbox['x2']]
            
            if hand_region.size == 0:
                continue
                
            # Run YOLO on hand region
            results = self.model(hand_region, conf=0.5, verbose=False)
            
            for result in results:
                boxes = result.boxes
                if boxes is not None:
                    for box in boxes:
                        # Get object info
                        x1, y1, x2, y2 = box.xyxy[0].cpu().numpy()
                        confidence = box.conf[0].cpu().numpy()
                        class_id = int(box.cls[0].cpu().numpy())
                        class_name = self.model.names[class_id]
                        
                        # Convert coordinates back to full frame
                        abs_x1 = int(bbox['x1'] + x1)
                        abs_y1 = int(bbox['y1'] + y1)
                        abs_x2 = int(bbox['x1'] + x2)
                        abs_y2 = int(bbox['y1'] + y2)
                        
                        detected_objects.append({
                            'bbox': [abs_x1, abs_y1, abs_x2, abs_y2],
                            'class_name': class_name,
                            'confidence': float(confidence),
                            'hand_bbox': bbox,
                            'crop': hand_region[int(y1):int(y2), int(x1):int(x2)]
                        })
        
        return detected_objects
    
    def draw_objects(self, frame, objects):
        """Draw detected objects"""
        for obj in objects:
            x1, y1, x2, y2 = obj['bbox']
            
            # Draw object bounding box
            cv2.rectangle(frame, (x1, y1), (x2, y2), (255, 0, 0), 2)
            
            # Draw label
            label = f"{obj['class_name']} ({obj['confidence']:.2f})"
            cv2.putText(frame, label, (x1, y1 - 10),
                       cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 0, 0), 2)
        
        return frame

class WasteClassifier:
    """Stage 3: Waste Classification using MobileNetV2"""
    
    def __init__(self):
        self.model = None
        self.class_names = [
            'plastic_bottle', 'aluminum_can', 'paper', 'cardboard', 'glass_bottle',  # recyclable
            'food_waste', 'fruit_peel', 'vegetable_waste', 'organic_matter',        # organic  
            'battery', 'electronics', 'general_waste', 'unknown'                    # other
        ]
        
        self.category_mapping = {
            'plastic_bottle': 'recyclable', 'aluminum_can': 'recyclable',
            'paper': 'recyclable', 'cardboard': 'recyclable', 'glass_bottle': 'recyclable',
            'food_waste': 'organic', 'fruit_peel': 'organic', 
            'vegetable_waste': 'organic', 'organic_matter': 'organic',
            'battery': 'other', 'electronics': 'other', 'general_waste': 'other',
            'unknown': 'other'
        }
        
        self.load_model()
    
    def load_model(self):
        """Load hoặc tạo model classification"""
        try:
            self.model = tf.keras.models.load_model('models/waste_classifier.h5')
            print("✅ Loaded pre-trained waste classifier")
        except:
            print("⚠️ Creating simple waste classifier for demo")
            self.model = self.create_demo_model()
    
    def create_demo_model(self):
        """Tạo model demo đơn giản"""
        # MobileNetV2 base
        base_model = tf.keras.applications.MobileNetV2(
            weights='imagenet',
            include_top=False,
            input_shape=(224, 224, 3)
        )
        base_model.trainable = False
        
        model = tf.keras.Sequential([
            base_model,
            tf.keras.layers.GlobalAveragePooling2D(),
            tf.keras.layers.Dropout(0.2),
            tf.keras.layers.Dense(128, activation='relu'),
            tf.keras.layers.Dropout(0.2),
            tf.keras.layers.Dense(len(self.class_names), activation='softmax')
        ])
        
        model.compile(
            optimizer='adam',
            loss='categorical_crossentropy',
            metrics=['accuracy']
        )
        
        return model
    
    def preprocess_image(self, image):
        """Preprocess image cho classification"""
        if image is None or image.size == 0:
            return None
            
        # Resize về 224x224
        image = cv2.resize(image, (224, 224))
        image = image.astype(np.float32) / 255.0
        image = np.expand_dims(image, axis=0)
        return image
    
    def classify_waste(self, objects_data):
        """Classify waste cho từng detected object"""
        classifications = []
        
        for obj in objects_data:
            if 'crop' not in obj or obj['crop'] is None:
                continue
                
            # Preprocess
            processed_img = self.preprocess_image(obj['crop'])
            if processed_img is None:
                continue
            
            try:
                # Predict
                predictions = self.model.predict(processed_img, verbose=0)
                class_idx = np.argmax(predictions[0])
                confidence = predictions[0][class_idx]
                
                waste_type = self.class_names[class_idx]
                category = self.category_mapping[waste_type]
                
                classification = {
                    'object_info': obj,
                    'waste_type': waste_type,
                    'category': category,
                    'confidence': float(confidence),
                    'description': self.get_description(waste_type, category)
                }
                
                classifications.append(classification)
                
            except Exception as e:
                print(f"Classification error: {e}")
                continue
        
        return classifications
    
    def get_description(self, waste_type, category):
        """Get mô tả về loại rác"""
        descriptions = {
            'recyclable': f"♻️ {waste_type} - Có thể tái chế",
            'organic': f"🌱 {waste_type} - Rác hữu cơ", 
            'other': f"🗑️ {waste_type} - Rác thông thường"
        }
        return descriptions.get(category, f"❓ {waste_type}")

class PlacementValidator:
    """Stage 4: Placement Validation"""
    
    def __init__(self):
        self.movement_history = deque(maxlen=30)  # 30 frames history
        self.placement_threshold = 100  # pixels
        self.validation_frames = 10  # số frames để confirm placement
        
    def track_movement(self, classifications, marked_bins):
        """Track object movement và validate placement"""
        current_time = time.time()
        
        validation_results = []
        
        for classification in classifications:
            obj = classification['object_info']
            obj_center = self.get_object_center(obj['bbox'])
            
            # Add to movement history
            movement_data = {
                'timestamp': current_time,
                'position': obj_center,
                'category': classification['category'],
                'confidence': classification['confidence']
            }
            self.movement_history.append(movement_data)
            
            # Check placement
            placement_result = self.check_placement(
                obj_center, classification['category'], marked_bins
            )
            
            if placement_result:
                validation_results.append({
                    'classification': classification,
                    'placement': placement_result,
                    'timestamp': current_time
                })
        
        return validation_results
    
    def get_object_center(self, bbox):
        """Get center point của object"""
        x1, y1, x2, y2 = bbox
        return [(x1 + x2) // 2, (y1 + y2) // 2]
    
    def check_placement(self, obj_center, obj_category, marked_bins):
        """Check xem object có được place vào đúng bin không"""
        for bin_info in marked_bins:
            # Check if object center is inside bin area
            if self.point_in_bin(obj_center, bin_info):
                is_correct = (bin_info['type'] == obj_category)
                
                return {
                    'bin_info': bin_info,
                    'is_correct': is_correct,
                    'distance': self.calculate_distance(obj_center, bin_info),
                    'message': self.get_placement_message(is_correct, bin_info, obj_category)
                }
        
        return None
    
    def point_in_bin(self, point, bin_info):
        """Check if point is inside bin area"""
        x, y = point
        return (bin_info['x'] <= x <= bin_info['x'] + bin_info['width'] and
                bin_info['y'] <= y <= bin_info['y'] + bin_info['height'])
    
    def calculate_distance(self, obj_center, bin_info):
        """Calculate distance from object to bin center"""
        bin_center = [
            bin_info['x'] + bin_info['width'] // 2,
            bin_info['y'] + bin_info['height'] // 2
        ]
        return np.sqrt((obj_center[0] - bin_center[0])**2 + 
                      (obj_center[1] - bin_center[1])**2)
    
    def get_placement_message(self, is_correct, bin_info, obj_category):
        """Generate placement feedback message"""
        if is_correct:
            return f"✅ Chính xác! Đã bỏ vào thùng {bin_info['label']}"
        else:
            return f"❌ Sai! Đây là thùng {bin_info['label']}, nhưng vật này thuộc loại {obj_category}"

class RealTimeAIPipeline:
    """Main pipeline coordinator - Real-time optimized"""
    
    def __init__(self):
        # Initialize all stages
        self.hand_detector = HandDetector()
        self.object_detector = ObjectDetector()
        self.waste_classifier = WasteClassifier()
        self.placement_validator = PlacementValidator()
        
        # Performance optimization
        self.frame_skip = 2  # Process every 2nd frame for speed
        self.frame_count = 0
        self.processing_time_history = deque(maxlen=30)
        
        # Threading for async processing
        self.processing_queue = deque(maxlen=5)
        self.result_queue = deque(maxlen=10)
        self.is_processing = False
        
    def process_frame_sync(self, frame, marked_bins):
        """Synchronous processing - simpler but slower"""
        start_time = time.time()
        
        # Stage 1: Hand Detection
        hands_data = self.hand_detector.detect_hands(frame)
        
        # Stage 2: Object Detection
        objects_data = self.object_detector.detect_objects_in_hands(frame, hands_data)
        
        # Stage 3: Waste Classification  
        classifications = self.waste_classifier.classify_waste(objects_data)
        
        # Stage 4: Placement Validation
        validations = self.placement_validator.track_movement(classifications, marked_bins)
        
        # Calculate processing time
        processing_time = time.time() - start_time
        self.processing_time_history.append(processing_time)
        
        return {
            'hands': hands_data,
            'objects': objects_data,
            'classifications': classifications,
            'validations': validations,
            'processing_time': processing_time,
            'fps': 1.0 / processing_time if processing_time > 0 else 0
        }
    
    async def process_frame_async(self, frame, marked_bins):
        """Asynchronous processing - faster"""
        self.frame_count += 1
        
        # Skip frames for performance
        if self.frame_count % self.frame_skip != 0:
            return self.get_last_result()
        
        # Add to processing queue
        self.processing_queue.append({
            'frame': frame.copy(),
            'marked_bins': marked_bins,
            'timestamp': time.time()
        })
        
        # Process in background thread
        if not self.is_processing and self.processing_queue:
            self.is_processing = True
            threading.Thread(target=self._process_async_worker, daemon=True).start()
        
        return self.get_last_result()
    
    def _process_async_worker(self):
        """Background worker for async processing"""
        try:
            while self.processing_queue:
                task = self.processing_queue.popleft()
                result = self.process_frame_sync(task['frame'], task['marked_bins'])
                result['timestamp'] = task['timestamp']
                
                self.result_queue.append(result)
                
        finally:
            self.is_processing = False
    
    def get_last_result(self):
        """Get latest processing result"""
        if self.result_queue:
            return self.result_queue[-1]
        else:
            return {
                'hands': [], 'objects': [], 'classifications': [], 
                'validations': [], 'processing_time': 0, 'fps': 0
            }
    
    def draw_all_results(self, frame, result):
        """Draw tất cả results lên frame"""
        # Draw hands
        if result['hands']:
            frame = self.hand_detector.draw_hands(frame, result['hands'])
        
        # Draw objects
        if result['objects']:
            frame = self.object_detector.draw_objects(frame, result['objects'])
        
        # Draw classifications
        self.draw_classifications(frame, result['classifications'])
        
        # Draw validations
        self.draw_validations(frame, result['validations'])
        
        # Draw performance info
        self.draw_performance_info(frame, result)
        
        return frame
    
    def draw_classifications(self, frame, classifications):
        """Draw classification results"""
        y_offset = 30
        
        for i, classification in enumerate(classifications):
            text = f"{classification['description']} ({classification['confidence']:.2f})"
            
            # Choose color based on category
            colors = {'recyclable': (0, 255, 0), 'organic': (0, 165, 255), 'other': (0, 0, 255)}
            color = colors.get(classification['category'], (255, 255, 255))
            
            cv2.putText(frame, text, (10, y_offset + i * 25),
                       cv2.FONT_HERSHEY_SIMPLEX, 0.7, color, 2)
    
    def draw_validations(self, frame, validations):
        """Draw placement validation results"""
        for validation in validations:
            if validation['placement']:
                placement = validation['placement']
                message = placement['message']
                
                # Draw message
                color = (0, 255, 0) if placement['is_correct'] else (0, 0, 255)
                cv2.putText(frame, message, (10, frame.shape[0] - 30),
                           cv2.FONT_HERSHEY_SIMPLEX, 0.8, color, 2)
    
    def draw_performance_info(self, frame, result):
        """Draw performance information"""
        fps_text = f"FPS: {result['fps']:.1f}"
        processing_time_text = f"Processing: {result['processing_time']*1000:.1f}ms"
        
        cv2.putText(frame, fps_text, (frame.shape[1] - 150, 30),
                   cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 255), 2)
        cv2.putText(frame, processing_time_text, (frame.shape[1] - 200, 60),
                   cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 255), 2)
    
    def get_performance_stats(self):
        """Get performance statistics"""
        if not self.processing_time_history:
            return {'avg_fps': 0, 'avg_processing_time': 0}
        
        avg_processing_time = np.mean(self.processing_time_history)
        avg_fps = 1.0 / avg_processing_time if avg_processing_time > 0 else 0
        
        return {
            'avg_fps': avg_fps,
            'avg_processing_time': avg_processing_time,
            'min_processing_time': min(self.processing_time_history),
            'max_processing_time': max(self.processing_time_history)
        }

# Demo và test script
def demo_realtime_pipeline():
    """Demo real-time pipeline với webcam"""
    pipeline = RealTimeAIPipeline()
    
    # Mock marked bins for demo
    marked_bins = [
        {'type': 'recyclable', 'label': 'Tái chế ♻️', 'x': 100, 'y': 100, 'width': 150, 'height': 150},
        {'type': 'organic', 'label': 'Hữu cơ 🌱', 'x': 300, 'y': 100, 'width': 150, 'height': 150},
        {'type': 'other', 'label': 'Khác 🗑️', 'x': 500, 'y': 100, 'width': 150, 'height': 150}
    ]
    
    # Start webcam
    cap = cv2.VideoCapture(0)
    cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
    cap.set(cv2.CAP_PROP_FPS, 30)
    
    print("🚀 Starting real-time AI pipeline...")
    print("📷 Press 'q' to quit, 's' for sync mode, 'a' for async mode")
    
    async_mode = False
    
    while True:
        ret, frame = cap.read()
        if not ret:
            break
        
        # Process frame
        if async_mode:
            # Async processing (would need proper async setup)
            result = pipeline.process_frame_sync(frame, marked_bins)
        else:
            # Sync processing
            result = pipeline.process_frame_sync(frame, marked_bins)
        
        # Draw results
        annotated_frame = pipeline.draw_all_results(frame, result)
        
        # Draw marked bins
        for bin_info in marked_bins:
            cv2.rectangle(annotated_frame, 
                         (bin_info['x'], bin_info['y']),
                         (bin_info['x'] + bin_info['width'], bin_info['y'] + bin_info['height']),
                         (128, 128, 128), 2)
            cv2.putText(annotated_frame, bin_info['label'],
                       (bin_info['x'], bin_info['y'] - 10),
                       cv2.FONT_HERSHEY_SIMPLEX, 0.7, (128, 128, 128), 2)
        
        # Show frame
        cv2.imshow('Waste Sorting AI Pipeline', annotated_frame)
        
        # Handle key presses
        key = cv2.waitKey(1) & 0xFF
        if key == ord('q'):
            break
        elif key == ord('s'):
            async_mode = False
            print("Switched to sync mode")
        elif key == ord('a'):
            async_mode = True
            print("Switched to async mode")
    
    cap.release()
    cv2.destroyAllWindows()
    
    # Print performance stats
    stats = pipeline.get_performance_stats()
    print(f"\n📊 Performance Stats:")
    print(f"Average FPS: {stats['avg_fps']:.1f}")
    print(f"Average Processing Time: {stats['avg_processing_time']*1000:.1f}ms")

if __name__ == "__main__":
    demo_realtime_pipeline()



    # 🤖 GitHub Copilot Integration Guide - AI Waste Sorting với WordPress

## 📝 Cách sử dụng guide này với GitHub Copilot

### 1. Copy các comment templates và paste vào code
### 2. Nhấn Tab để chấp nhận suggestions của Copilot
### 3. Sử dụng Ctrl+Enter để xem multiple options
### 4. Follow step-by-step implementation guide

---

## 🎯 PHASE 1: Setup Backend API Server

### Bước 1.1: Tạo FastAPI Server Integration

```python
# COPILOT PROMPT: Create FastAPI server that integrates the AI pipeline with WordPress
# Requirements: 
# - Support CORS for WordPress domain
# - Handle file uploads from WordPress frontend
# - Convert AI pipeline results to WordPress-compatible JSON format
# - Add error handling and logging
# - Support both HTTP and WebSocket endpoints

# main.py
from fastapi import FastAPI, File, UploadFile, HTTPException, WebSocket
from fastapi.middleware.cors import CORSMiddleware
import cv2
import numpy as np
from PIL import Image
import io
import base64
import json

# COPILOT: Import the existing AI pipeline
from realtime_ai_pipeline import RealTimeAIPipeline

app = FastAPI(title="WordPress AI Waste Sorting API")

# COPILOT: Configure CORS for WordPress integration
# Allow WordPress domain, handle preflight requests, support file uploads

# COPILOT: Initialize global AI pipeline instance
# Add error handling for model loading failures

# COPILOT: Create endpoint /api/wordpress/process-frame
# Accept multipart/form-data with image and marked_bins JSON
# Return WordPress-compatible response format

# COPILOT: Create endpoint /api/wordpress/health-check  
# Return API status, model loading status, performance metrics

# COPILOT: Create WebSocket endpoint /ws/wordpress
# Handle real-time frame processing for WordPress frontend
# Support connection management and error recovery

if __name__ == "__main__":
    # COPILOT: Add uvicorn server configuration for production
    pass
```

### Bước 1.2: Tạo WordPress API Wrapper

```python
# COPILOT PROMPT: Create WordPress-specific API wrapper
# Requirements:
# - Convert AI results to WordPress post/meta format
# - Handle WordPress authentication/nonces
# - Support WordPress REST API integration
# - Add caching for frequently requested data

# wordpress_api_wrapper.py
class WordPressAIWrapper:
    """
    COPILOT: Create class to handle WordPress-specific functionality
    - Convert AI pipeline results to WordPress-compatible format
    - Handle WordPress REST API authentication
    - Manage session data and user preferences
    - Cache frequently used data
    """
    
    def __init__(self, wordpress_config):
        # COPILOT: Initialize WordPress connection and AI pipeline
        pass
    
    def process_wordpress_frame(self, image_data, user_id, marked_bins):
        """
        COPILOT: Process frame specifically for WordPress context
        - Extract user preferences from WordPress database
        - Apply user-specific model configurations
        - Log activity to WordPress database
        - Return results in WordPress format
        """
        pass
    
    def save_sorting_session(self, user_id, session_data):
        """
        COPILOT: Save sorting session to WordPress database
        - Create custom post type for sorting sessions
        - Store performance metrics as post meta
        - Handle user privacy settings
        """
        pass
    
    def get_user_sorting_history(self, user_id, limit=10):
        """
        COPILOT: Retrieve user's sorting history from WordPress
        - Query custom posts by user
        - Include performance statistics
        - Format for frontend display
        """
        pass
```

---

## 🔌 PHASE 2: WordPress Plugin Development

### Bước 2.1: Main Plugin File

```php
<?php
/**
 * Plugin Name: AI Waste Sorting System
 * Description: Real-time AI-powered waste sorting with camera integration
 * Version: 2.0.0
 * Author: Your Name
 */

// COPILOT PROMPT: Create main WordPress plugin file
// Requirements:
// - Handle plugin activation/deactivation
// - Register custom post types for sorting sessions
// - Enqueue scripts and styles with proper dependencies
// - Add admin settings page for API configuration
// - Create shortcode for frontend integration

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// COPILOT: Define plugin constants for paths and URLs

// COPILOT: Create main plugin class WasteortingAI
class WasteSortingAI {
    
    // COPILOT: Add class properties for API settings, user options, plugin paths
    
    public function __construct() {
        // COPILOT: Hook into WordPress actions and filters
        // - init: Initialize plugin
        // - wp_enqueue_scripts: Load frontend assets
        // - admin_menu: Add admin pages
        // - wp_ajax_*: Handle AJAX requests
        // - add_shortcode: Register shortcodes
    }
    
    public function init() {
        // COPILOT: Initialize plugin functionality
        // - Register custom post types
        // - Create database tables if needed
        // - Load text domain for internationalization
    }
    
    public function enqueue_scripts() {
        // COPILOT: Enqueue scripts and styles for frontend
        // - Load MediaDevices API support
        // - Include WebSocket connection library
        // - Add TensorFlow.js for client-side optimization
        // - Localize script with AJAX URL and nonces
    }
    
    public function admin_menu() {
        // COPILOT: Create admin interface
        // - Settings page for API configuration
        // - Dashboard for monitoring system performance
        // - User activity logs and analytics
    }
    
    public function ajax_process_frame() {
        // COPILOT: Handle AJAX request for frame processing
        // - Verify nonce for security
        // - Validate uploaded image
        // - Forward to Python API
        // - Return formatted response
    }
    
    public function camera_shortcode($atts) {
        // COPILOT: Create shortcode for camera interface
        // - Parse shortcode attributes
        // - Include HTML template
        // - Add inline JavaScript configuration
        // - Handle responsive design
    }
}

// COPILOT: Initialize plugin instance and handle activation/deactivation hooks
```

### Bước 2.2: Frontend JavaScript Integration

```javascript
// COPILOT PROMPT: Create WordPress-compatible JavaScript integration
// Requirements:
// - Work with WordPress jQuery and other plugins
// - Handle WordPress AJAX with proper nonces
// - Support WordPress media library integration
// - Use WordPress REST API when available
// - Handle WordPress user authentication

// assets/js/wordpress-waste-sorting.js
class WordPressWasteSorting {
    /**
     * COPILOT: Create main class for WordPress integration
     * - Initialize with WordPress-specific configuration
     * - Handle WordPress AJAX endpoints
     * - Support WordPress user session management
     * - Integrate with WordPress media library
     */
    
    constructor(wpConfig) {
        // COPILOT: Initialize with WordPress configuration
        // - API endpoints from wp_localize_script
        // - User authentication tokens
        // - WordPress-specific settings
    }
    
    initCamera() {
        /**
         * COPILOT: Initialize camera with WordPress context
         * - Check WordPress user permissions
         * - Load user preferences from WordPress database
         * - Set up error handling for WordPress admin notices
         */
    }
    
    processFrameWordPress(imageData) {
        /**
         * COPILOT: Process frame through WordPress AJAX
         * - Use WordPress AJAX action
         * - Include nonce for security
         * - Handle WordPress user context
         * - Save results to WordPress database
         */
    }
    
    saveToMediaLibrary(imageData, metadata) {
        /**
         * COPILOT: Save processed images to WordPress media library
         * - Use WordPress media endpoint
         * - Add proper metadata and alt text
         * - Associate with current user
         * - Handle WordPress file permissions
         */
    }
    
    updateUserProfile(sortingStats) {
        /**
         * COPILOT: Update user profile with sorting statistics
         * - Use WordPress user meta API
         * - Update custom user fields
         * - Trigger WordPress action hooks
         * - Handle privacy settings
         */
    }
}

// COPILOT: Initialize when WordPress document is ready
jQuery(document).ready(function($) {
    // COPILOT: Initialize WordPress waste sorting system
    // - Use jQuery from WordPress
    // - Access wp_localize_script data
    // - Set up WordPress-specific event handlers
});
```

### Bước 2.3: WordPress Database Integration

```php
<?php
// COPILOT PROMPT: Create WordPress database integration
// Requirements:
// - Custom post types for sorting sessions
// - User meta for preferences and statistics
// - Custom tables for performance data
// - WordPress caching integration
// - Database migration handling

// includes/class-database.php
class WasteSorting_Database {
    
    /**
     * COPILOT: Create database handler for WordPress
     * - Use WordPress $wpdb for database operations
     * - Follow WordPress coding standards
     * - Handle database versioning and migrations
     * - Support multisite installations
     */
    
    public function __construct() {
        // COPILOT: Initialize database connection and hooks
    }
    
    public function create_tables() {
        /**
         * COPILOT: Create custom database tables
         * - Use dbDelta for WordPress-compatible table creation
         * - Add proper indexes for performance
         * - Include proper charset and collation
         * - Handle table versioning
         */
    }
    
    public function register_post_types() {
        /**
         * COPILOT: Register custom post types
         * - Sorting sessions post type
         * - Performance logs post type
         * - User preferences post type
         * - Add proper capabilities and permissions
         */
    }
    
    public function save_sorting_session($user_id, $session_data) {
        /**
         * COPILOT: Save sorting session to WordPress
         * - Create new post with session data
         * - Add custom fields for AI results
         * - Handle image attachments
         * - Set proper post status and visibility
         */
    }
    
    public function get_user_statistics($user_id) {
        /**
         * COPILOT: Get user sorting statistics
         * - Query posts and meta data
         * - Calculate performance metrics
         * - Use WordPress caching for optimization
         * - Return formatted data array
         */
    }
}
```

---

## 🎨 PHASE 3: Frontend Templates và UI

### Bước 3.1: WordPress Template Integration

```php
<?php
// COPILOT PROMPT: Create WordPress template for camera interface
// Requirements:
// - Support WordPress themes and responsive design
// - Include proper WordPress header/footer
// - Handle WordPress user permissions
// - Add WordPress admin bar compatibility
// - Support RTL languages

// templates/camera-interface.php
?>
<div id="waste-sorting-app" class="wp-waste-sorting">
    <?php
    // COPILOT: Add WordPress security checks and user permission validation
    
    // COPILOT: Include WordPress theme compatibility styles
    
    // COPILOT: Create camera interface HTML structure
    // - Video element with WordPress-compatible styling
    // - Control buttons with WordPress button classes  
    // - Results display with WordPress notice styling
    // - Progress indicators with WordPress admin styling
    ?>
    
    <div class="camera-container">
        <!-- COPILOT: Generate camera interface HTML -->
    </div>
    
    <div class="results-container">
        <!-- COPILOT: Generate results display HTML -->
    </div>
    
    <div class="controls-container">
        <!-- COPILOT: Generate control buttons HTML -->
    </div>
</div>

<script type="text/javascript">
// COPILOT: Initialize WordPress-compatible JavaScript
// - Use WordPress jQuery
// - Access localized script data
// - Handle WordPress admin AJAX
</script>
```

### Bước 3.2: WordPress Admin Interface

```php
<?php
// COPILOT PROMPT: Create WordPress admin interface
// Requirements:
// - Use WordPress admin styling and components
// - Add settings pages with WordPress Settings API
// - Create dashboard widgets for monitoring
// - Include help tabs and contextual help
// - Support WordPress user roles and capabilities

// admin/admin-interface.php
class WasteSorting_Admin {
    
    /**
     * COPILOT: Create admin interface class
     * - Register admin menus and submenus
     * - Add settings pages with WordPress Settings API
     * - Create dashboard widgets
     * - Handle admin AJAX requests
     */
    
    public function add_admin_menu() {
        /**
         * COPILOT: Add admin menu items
         * - Main menu page for waste sorting
         * - Settings submenu
         * - Analytics submenu
         * - User management submenu
         * - Set proper capabilities for each menu
         */
    }
    
    public function settings_page() {
        /**
         * COPILOT: Create settings page
         * - API configuration form
         * - Model settings
         * - User permission settings
         * - Performance optimization options
         * - Use WordPress Settings API
         */
    }
    
    public function dashboard_widget() {
        /**
         * COPILOT: Create dashboard widget
         * - Show system status
         * - Display recent activity
         * - Performance metrics
         * - Quick actions
         */
    }
    
    public function analytics_page() {
        /**
         * COPILOT: Create analytics page
         * - User activity charts
         * - System performance graphs
         * - Sorting accuracy statistics
         * - Export functionality
         */
    }
}
```

---

## 🔄 PHASE 4: Real-time WebSocket Integration

### Bước 4.1: WordPress WebSocket Handler

```javascript
// COPILOT PROMPT: Create WordPress-compatible WebSocket integration
// Requirements:
// - Handle WordPress authentication through WebSocket
// - Support WordPress user sessions
// - Include reconnection logic for WordPress environment
// - Handle WordPress security nonces
// - Support WordPress multisite

// assets/js/wordpress-websocket.js
class WordPressWebSocketHandler {
    /**
     * COPILOT: Create WebSocket handler for WordPress
     * - Initialize with WordPress user authentication
     * - Handle WordPress-specific message formatting
     * - Include error handling for WordPress admin notices
     * - Support WordPress heartbeat API integration
     */
    
    constructor(wpConfig) {
        // COPILOT: Initialize WebSocket with WordPress configuration
    }
    
    connect() {
        /**
         * COPILOT: Establish WebSocket connection
         * - Include WordPress user token in connection
         * - Handle WordPress security validation
         * - Set up WordPress-compatible event handlers
         * - Add connection status display for WordPress admin
         */
    }
    
    sendFrame(imageData, userContext) {
        /**
         * COPILOT: Send frame data through WebSocket
         * - Include WordPress user context
         * - Add WordPress nonce for security
         * - Handle WordPress user permissions
         * - Format data for WordPress backend
         */
    }
    
    handleResponse(data) {
        /**
         * COPILOT: Handle WebSocket response
         * - Process AI results for WordPress
         * - Update WordPress frontend interface
         * - Save data to WordPress database
         * - Trigger WordPress action hooks
         */
    }
    
    reconnect() {
        /**
         * COPILOT: Handle WebSocket reconnection
         * - Refresh WordPress authentication
         * - Restore user session context
         * - Display WordPress admin notices
         * - Log events to WordPress error log
         */
    }
}
```

### Bước 4.2: WordPress REST API Integration

```php
<?php
// COPILOT PROMPT: Create WordPress REST API endpoints
// Requirements:
// - Follow WordPress REST API standards
// - Include proper authentication and permissions
// - Support WordPress REST API caching
// - Add rate limiting for API endpoints
// - Include API documentation

// includes/class-rest-api.php
class WasteSorting_REST_API {
    
    /**
     * COPILOT: Create REST API class for WordPress
     * - Register custom REST API endpoints
     * - Handle authentication and permissions
     * - Include input validation and sanitization
     * - Add proper error responses
     */
    
    public function register_routes() {
        /**
         * COPILOT: Register REST API routes
         * - POST /wp-json/waste-sorting/v1/process-frame
         * - GET /wp-json/waste-sorting/v1/user-stats
         * - POST /wp-json/waste-sorting/v1/save-session
         * - GET /wp-json/waste-sorting/v1/system-status
         */
    }
    
    public function process_frame_endpoint($request) {
        /**
         * COPILOT: Handle frame processing endpoint
         * - Validate uploaded image
         * - Check user permissions
         * - Forward to AI API
         * - Return WordPress-formatted response
         */
    }
    
    public function user_stats_endpoint($request) {
        /**
         * COPILOT: Handle user statistics endpoint
         * - Validate user authentication
         * - Query WordPress database for user data
         * - Calculate performance metrics
         * - Return formatted statistics
         */
    }
    
    public function save_session_endpoint($request) {
        /**
         * COPILOT: Handle session saving endpoint
         * - Validate session data
         * - Check user permissions
         * - Save to WordPress database
         * - Update user meta data
         */
    }
}
```

---

## 📱 PHASE 5: Responsive Design và Mobile Support

### Bước 5.1: WordPress Mobile Integration

```css
/* COPILOT PROMPT: Create responsive CSS for WordPress themes
   Requirements:
   - Compatible with popular WordPress themes
   - Support WordPress block editor styles
   - Include RTL language support
   - Handle WordPress admin bar on mobile
   - Support WordPress accessibility standards */

/* assets/css/wordpress-mobile.css */

/* COPILOT: Add WordPress theme compatibility styles */
.wp-waste-sorting {
    /* COPILOT: Create base styles compatible with WordPress themes */
}

/* COPILOT: Add responsive breakpoints for WordPress */
@media (max-width: 768px) {
    /* COPILOT: Mobile-specific styles for WordPress environment */
}

/* COPILOT: Add WordPress admin bar compatibility */
.admin-bar .wp-waste-sorting {
    /* COPILOT: Adjust layout for WordPress admin bar */
}

/* COPILOT: Add RTL language support */
.rtl .wp-waste-sorting {
    /* COPILOT: RTL-specific styles */
}

/* COPILOT: Add WordPress accessibility support */
.wp-waste-sorting:focus-within {
    /* COPILOT: Accessibility focus styles */
}
```

### Bước 5.2: WordPress Block Editor Integration

```javascript
// COPILOT PROMPT: Create Gutenberg block for waste sorting camera
// Requirements:
// - Follow WordPress block development standards
// - Support block editor features (align, spacing, etc.)
// - Include block variations for different use cases
// - Add block controls and settings panel
// - Support WordPress theme colors and fonts

// blocks/waste-sorting-camera/index.js
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, RangeControl } from '@wordpress/components';

// COPILOT: Register Gutenberg block for waste sorting camera
registerBlockType('waste-sorting/camera', {
    // COPILOT: Add block configuration
    // - title, description, category, icon
    // - supports: align, spacing, color
    // - attributes for camera settings
    
    edit: function(props) {
        // COPILOT: Create block editor interface
        // - Camera preview in editor
        // - Settings panel controls
        // - Block alignment options
        // - Preview mode toggle
    },
    
    save: function(props) {
        // COPILOT: Create saved block output
        // - Generate shortcode or direct HTML
        // - Include block attributes
        // - Handle dynamic content
    }
});
```

---

## 🔒 PHASE 6: Security và Performance

### Bước 6.1: WordPress Security Integration

```php
<?php
// COPILOT PROMPT: Add WordPress security measures
// Requirements:
// - Validate and sanitize all input data
// - Use WordPress nonces for AJAX requests
// - Check user capabilities and permissions
// - Prevent direct file access
// - Add rate limiting for API calls

// includes/class-security.php
class WasteSorting_Security {
    
    /**
     * COPILOT: Create security handler class
     * - Input validation and sanitization
     * - Nonce verification for AJAX
     * - User capability checks
     * - File upload security
     * - API rate limiting
     */
    
    public function validate_upload($file) {
        /**
         * COPILOT: Validate uploaded images
         * - Check file type and size
         * - Validate image dimensions
         * - Scan for malicious content
         * - Use WordPress file validation functions
         */
    }
    
    public function verify_user_permissions($user_id, $action) {
        /**
         * COPILOT: Check user permissions
         * - Verify user capabilities
         * - Check if user can upload files
         * - Validate action permissions
         * - Log security events
         */
    }
    
    public function rate_limit_check($user_id, $endpoint) {
        /**
         * COPILOT: Implement rate limiting
         * - Track user API calls
         * - Set limits per user role
         * - Use WordPress transients for storage
         * - Return appropriate error messages
         */
    }
}
```

### Bước 6.2: WordPress Caching Integration

```php
<?php
// COPILOT PROMPT: Add WordPress caching support
// Requirements:
// - Support popular WordPress caching plugins
// - Cache AI model results appropriately
// - Handle cache invalidation properly
// - Support WordPress object caching
// - Add cache warming for better performance

// includes/class-cache.php
class WasteSorting_Cache {
    
    /**
     * COPILOT: Create caching handler for WordPress
     * - Use WordPress transients API
     * - Support object caching
     * - Handle cache invalidation
     * - Optimize for popular caching plugins
     */
    
    public function cache_ai_result($key, $data, $expiration = 3600) {
        /**
         * COPILOT: Cache AI processing results
         * - Use WordPress transients
         * - Set appropriate expiration
         * - Handle cache key generation
         * - Support multisite caching
         */
    }
    
    public function get_cached_result($key) {
        /**
         * COPILOT: Retrieve cached AI results
         * - Check WordPress transients
         * - Validate cached data
         * - Handle cache misses
         * - Log cache performance
         */
    }
    
    public function invalidate_user_cache($user_id) {
        /**
         * COPILOT: Clear user-specific cache
         * - Remove user transients
         * - Clear related cached data
         * - Update cache version
         * - Trigger cache rebuild
         */
    }
}
```

---

## 🚀 DEPLOYMENT GUIDE

### Step 1: Prepare Environment
```bash
# COPILOT PROMPT: Create deployment script for WordPress environment
# Requirements:
# - Check WordPress version compatibility
# - Verify required PHP extensions
# - Set up Python AI server
# - Configure WordPress database
# - Set up SSL certificates for WebSocket

# deploy.sh
#!/bin/bash

# COPILOT: Add WordPress environment checks
# - WordPress version >= 5.0
# - PHP version >= 7.4
# - Required PHP extensions
# - Database configuration
# - File permissions

# COPILOT: Set up Python AI server
# - Create virtual environment
# - Install dependencies
# - Configure systemd service
# - Set up nginx proxy
# - Configure SSL

# COPILOT: Configure WordPress plugin
# - Create database tables
# - Set default options
# - Configure API endpoints
# - Set up cron jobs
# - Initialize user roles
```

### Step 2: WordPress Configuration
```php
<?php
// COPILOT PROMPT: Add WordPress configuration for production
// Requirements:
// - Set up WordPress constants for AI API
// - Configure WordPress caching
// - Set up WordPress security headers
// - Configure WordPress multisite if needed
// - Add WordPress debugging for development

// wp-config.php additions
// COPILOT: Add AI waste sorting configuration constants
define('WASTE_SORTING_API_URL', 'https://your-domain.com/api');
define('WASTE_SORTING_WS_URL', 'wss://your-domain.com/ws');
define('WASTE_SORTING_API_KEY', 'your-secure-api-key');

// COPILOT: Add performance optimization settings
// COPILOT: Add security configuration
// COPILOT: Add debugging settings for development
```

---

## 📋 TESTING GUIDE

### Automated Testing with Copilot
```php
<?php
// COPILOT PROMPT: Create PHPUnit tests for WordPress plugin
// Requirements:
// - Test WordPress integration points
// - Mock AI API responses
// - Test user permissions and security
// - Test database operations
// - Test frontend JavaScript functionality

// tests/test-waste-sorting.php
class Test_WasteSorting extends WP_UnitTestCase {
    
    /**
     * COPILOT: Create test setup method
     * - Initialize WordPress test environment
     * - Create test users with different roles
     * - Set up mock AI API responses
     * - Create test data
     */
    
    public function test_plugin_activation() {
        /**
         * COPILOT: Test plugin activation
         * - Verify database tables creation
         * - Check default options setup
         * - Test user role capabilities
         * - Verify cron job registration
         */
    }
    
    public function test_camera_shortcode() {
        /**
         * COPILOT: Test camera shortcode
         * - Verify HTML output
         * - Test attribute parsing
         * - Check user permission handling
         * - Validate script enqueuing
         */
    }
    
    public function test_ajax_endpoints() {
        /**
         * COPILOT: Test AJAX functionality
         * - Test frame processing endpoint
         * - Verify nonce validation
         * - Test user authentication
         * - Check error handling
         */
    }
}
```

---

## 🎯 FINAL IMPLEMENTATION CHECKLIST

### Phase 1: ✅ Backend Setup
- [ ] Python AI server running
- [ ] FastAPI endpoints configured
- [ ] WebSocket server operational
- [ ] WordPress API wrapper created

### Phase 2: ✅ WordPress Plugin
- [ ] Main plugin file created
- [ ] Database tables setup
- [ ] Admin interface functional
- [ ] Shortcode registered

### Phase 3: ✅ Frontend Integration
- [ ] Camera interface working
- [ ] Real-time processing active
- [ ] Results display functional
- [ ] Mobile responsive design

### Phase 4: ✅ Security & Performance
- [ ] Input validation implemented
- [ ] User permissions configured
- [ ] Caching system active
- [ ] Rate limiting enabled

### Phase 5: ✅ Testing & Deployment
- [ ] Unit tests passing
- [ ] Integration tests successful
- [ ] Production deployment ready
- [ ] Documentation complete

---

## 🤖 Pro Tips cho GitHub Copilot

1. **Sử dụng comment patterns cụ thể**: Copilot hiểu rõ hơn khi bạn mô tả chính xác requirements
2. **Chia nhỏ functions**: Tạo nhiều functions nhỏ thay vì một function lớn
3. **Sử dụng type hints**: Python type hints giúp Copilot generate code chính xác hơn
4. **Follow WordPress standards**: Copilot được train trên WordPress codebase, follow standards sẽ cho kết quả tốt hơn
5. **Test driven development**: Viết test cases trước, Copilot sẽ generate implementation phù hợp

Bạn có thể bắt đầu implement từng phase một cách tuần tự. Copilot sẽ giúp generate code rất hiệu quả khi có các prompts chi tiết như trên!