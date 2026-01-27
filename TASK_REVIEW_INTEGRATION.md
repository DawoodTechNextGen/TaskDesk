# How to Add Review Buttons to Supervisor Dashboard

## Step 1: Run the SQL Migration
1. Open phpMyAdmin
2. Select your `task_management` database
3. Go to the SQL tab
4. Copy and paste the contents of `database/migration_task_review.sql`
5. Click "Go" to execute

## Step 2: Add JavaScript to Your Supervisor Pages

Add this script tag to any page where supervisors view their generated tasks (usually in the footer or before `</body>`):

```html
<script src="assets/js/task-review.js"></script>
```

## Step 3: Add Action Buttons to Task Cards/Rows

When displaying tasks in your supervisor dashboard, add these buttons based on task status:

### For Pending Review Tasks (status = 'pending_review'):
```html
<button onclick="showReviewModal(<?= $task['id'] ?>, '<?= addslashes($task['title']) ?>')" 
    class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded text-sm">
    Review Task
</button>
```

### For Expired Tasks (status = 'expired'):
```html
<button onclick="showReactivateModal(<?= $task['id'] ?>, '<?= addslashes($task['title']) ?>', '<?= $task['due_date'] ?>')" 
    class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-1 rounded text-sm">
    Reactivate
</button>
```

## Step 4: Update Task Status Display

Add these status badges to show the new statuses:

```php
<?php
$statusColors = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'working' => 'bg-blue-100 text-blue-800',
    'pending_review' => 'bg-purple-100 text-purple-800',
    'approved' => 'bg-green-100 text-green-800',
    'rejected' => 'bg-red-100 text-red-800',
    'needs_improvement' => 'bg-orange-100 text-orange-800',
    'expired' => 'bg-gray-100 text-gray-800'
];
?>

<span class="px-2 py-1 rounded-full text-xs font-semibold <?= $statusColors[$task['status']] ?>">
    <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
</span>
```

## Example Integration

Here's a complete example for a task card:

```php
<div class="task-card">
    <h4><?= $task['title'] ?></h4>
    <p><?= $task['description'] ?></p>
    
    <!-- Status Badge -->
    <span class="px-2 py-1 rounded-full text-xs <?= $statusColors[$task['status']] ?>">
        <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
    </span>
    
    <!-- Action Buttons -->
    <?php if ($task['status'] === 'pending_review'): ?>
        <button onclick="showReviewModal(<?= $task['id'] ?>, '<?= addslashes($task['title']) ?>')" 
            class="bg-indigo-600 text-white px-3 py-1 rounded">
            Review
        </button>
    <?php elseif ($task['status'] === 'expired'): ?>
        <button onclick="showReactivateModal(<?= $task['id'] ?>, '<?= addslashes($task['title']) ?>', '<?= $task['due_date'] ?>')" 
            class="bg-orange-600 text-white px-3 py-1 rounded">
            Reactivate
        </button>
    <?php endif; ?>
</div>
```

## What Happens Now

1. **Intern completes task** → Status changes to `pending_review`
2. **Supervisor sees "Review" button** → Can approve/reject/request improvements
3. **Task expires** → Supervisor sees "Reactivate" button → Can set new due date
4. **Approved tasks** → Show green "Approved" badge
5. **Rejected/Needs Improvement** → Intern can see feedback in `review_notes`
