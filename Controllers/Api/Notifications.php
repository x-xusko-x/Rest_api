<?php

namespace Rest_api\Controllers\Api;

use App\Controllers\Security_Controller;

class Notifications extends Api_controller
{
    public $Notifications_model;

    function __construct()
    {
        parent::__construct();
        
        // Initialize models
        $this->Notifications_model = model('App\Models\Notifications_model', false);
    }

    /**
     * GET /api/v1/notifications
     * List all notifications for authenticated user (read-only)
     */
    public function index()
    {
        try {
            $pagination = $this->_get_pagination_params(50, 100);
            
            // Security: Always use authenticated user ID, never accept user_id parameter
            if (!$this->authenticated_user_id) {
                $this->_api_list_response([], 0, 'notifications', $pagination, false);
                return;
            }

            $notifications_result = $this->Notifications_model->get_notifications(
                (int)$this->authenticated_user_id,
                $pagination['offset'],
                $pagination['limit']
            );
            
            $notifications = is_object($notifications_result) && isset($notifications_result->result) ? $notifications_result->result : [];
            $total = is_object($notifications_result) && isset($notifications_result->found_rows) ? $notifications_result->found_rows : 0;

            $formatted_notifications = [];
            foreach ($notifications as $notification) {
                $formatted_notifications[] = $this->_format_notification($notification);
            }

            $this->_api_list_response($formatted_notifications, $total, 'notifications', $pagination, true);
        } catch (\Exception $e) {
            log_message('error', 'API Notifications::index error: ' . $e->getMessage());
            $this->_api_error_response('Failed to retrieve notifications', 500);
        }
    }

    /**
     * GET /api/v1/notifications/{id}
     * Get a specific notification by ID (read-only)
     */
    public function show($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Notification ID is required'), 400);
                return;
            }

            $notification = $this->Notifications_model->get_one($id);

            if (!$notification || !$notification->id) {
                $this->_api_response(array('message' => 'Notification not found'), 404);
                return;
            }

            // Ensure notification belongs to authenticated user
            if ($notification->user_id != $this->authenticated_user_id) {
                $this->_api_response(array('message' => 'Unauthorized access to notification'), 403);
                return;
            }

            $this->_api_response(array(
                'notification' => $this->_format_notification($notification)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Notifications::show error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to retrieve notification',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * POST /api/v1/notifications/{id}/mark_read
     * Mark a notification as read
     */
    public function mark_read($id = null)
    {
        try {
            if (!$id) {
                $this->_api_response(array('message' => 'Notification ID is required'), 400);
                return;
            }

            $notification = $this->Notifications_model->get_one($id);

            if (!$notification || !$notification->id) {
                $this->_api_response(array('message' => 'Notification not found'), 404);
                return;
            }

            // Ensure notification belongs to authenticated user
            if ($notification->user_id != $this->authenticated_user_id) {
                $this->_api_response(array('message' => 'Unauthorized access to notification'), 403);
                return;
            }

            // Mark as read
            $success = $this->Notifications_model->ci_save(array('is_read' => 1), $id);

            if (!$success) {
                $this->_api_response(array('message' => 'Failed to mark notification as read'), 500);
                return;
            }

            $notification = $this->Notifications_model->get_one($id);

            log_message('info', 'API: Notification marked as read - ID: ' . $id);

            $this->_api_response(array(
                'message' => 'Notification marked as read',
                'notification' => $this->_format_notification($notification)
            ), 200);
        } catch (\Exception $e) {
            log_message('error', 'API Notifications::mark_read error: ' . $e->getMessage());
            $this->_api_response(array(
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * Format notification data for API response
     */
    private function _format_notification($notification)
    {
        return array(
            'id' => (int)$notification->id,
            'user_id' => (int)($notification->user_id ?? 0),
            'description' => $notification->description ?? '',
            'event' => $notification->event ?? '',
            'event_id' => (int)($notification->event_id ?? 0),
            'is_read' => (int)($notification->is_read ?? 0),
            'notify_to' => $notification->notify_to ?? '',
            'read_by' => $notification->read_by ?? '',
            'created_at' => $notification->created_at ?? null
        );
    }
}

