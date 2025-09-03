<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once('../../config.php');
require_once($CFG->libdir.'/grouplib.php');

$courseid = optional_param('courseid', 0, PARAM_INT);
$groupid = optional_param('groupid', 0, PARAM_INT);

// Require login first
require_login();

if (!$courseid) {
    // Get courses where user has the capability
    $courses = enrol_get_my_courses();
    $available_courses = array();
    
    foreach ($courses as $course) {
        $context = context_course::instance($course->id);
        if (has_capability('local/sendafirmas:manage', $context)) {
            $available_courses[] = $course;
        }
    }
    
    // Set up page for course selection
    $PAGE->set_url('/local/sendafirmas/index.php');
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title(get_string('pluginname', 'local_sendafirmas'));
    $PAGE->set_heading(get_string('pluginname', 'local_sendafirmas'));
    
    echo $OUTPUT->header();
    ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h2><?php echo get_string('pluginname', 'local_sendafirmas'); ?></h2>
                <p><?php echo get_string('selectcourse', 'local_sendafirmas'); ?>:</p>
                
                <?php if (empty($available_courses)): ?>
                    <div class="alert alert-warning">
                        <?php echo get_string('nocourses', 'local_sendafirmas'); ?>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($available_courses as $course): ?>
                            <a href="?courseid=<?php echo $course->id; ?>" class="list-group-item list-group-item-action">
                                <h5 class="mb-1"><?php echo format_string($course->fullname); ?></h5>
                                <p class="mb-1"><?php echo format_string($course->shortname); ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    echo $OUTPUT->footer();
    exit;
}

// Get course and check it exists
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

// Check capabilities
require_capability('local/sendafirmas:manage', context_course::instance($course->id));

// Set up page
$PAGE->set_url('/local/sendafirmas/index.php', array('courseid' => $courseid, 'groupid' => $groupid));
$PAGE->set_context(context_course::instance($course->id));
$PAGE->set_title(get_string('pluginname', 'local_sendafirmas'));
$PAGE->set_heading($course->fullname);

// Get groups for this course
$groups = groups_get_all_groups($courseid);

// If groupid is specified, validate it belongs to this course
if ($groupid && !isset($groups[$groupid])) {
    throw new moodle_exception('invalid_group', 'local_sendafirmas');
}

// Get group members if group is selected
$members = array();
if ($groupid) {
    $members = groups_get_members($groupid, 'u.id, u.firstname, u.lastname');
    
    // Check if users have signatures (profile field)
    foreach ($members as $member) {
        $profilefield = $DB->get_record_sql(
            "SELECT uid.data 
             FROM {user_info_data} uid 
             JOIN {user_info_field} uif ON uid.fieldid = uif.id 
             WHERE uid.userid = ? AND uif.shortname = 'firma'",
            array($member->id)
        );
        $member->has_signature = !empty($profilefield->data);
    }
}

echo $OUTPUT->header();

?>

<style>
/* Pure CSS styling without Bootstrap dependencies */
.sendafirmas-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.sendafirmas-breadcrumb {
    list-style: none;
    padding: 0;
    margin: 0 0 20px 0;
    display: flex;
    flex-wrap: wrap;
    background: #f8f9fa;
    padding: 8px 16px;
    border-radius: 4px;
}

.sendafirmas-breadcrumb li {
    margin-right: 8px;
}

.sendafirmas-breadcrumb li:not(:last-child)::after {
    content: " / ";
    margin-left: 8px;
    color: #6c757d;
}

.sendafirmas-breadcrumb a {
    color: #007bff;
    text-decoration: none;
}

.sendafirmas-breadcrumb a:hover {
    text-decoration: underline;
}

.sendafirmas-form-group {
    margin-bottom: 20px;
}

.sendafirmas-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.sendafirmas-select, .sendafirmas-input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 16px;
    background: white;
}

.sendafirmas-select:focus, .sendafirmas-input:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.sendafirmas-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.sendafirmas-table th,
.sendafirmas-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.sendafirmas-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #495057;
}

.sendafirmas-table tr:hover {
    background: #f8f9fa;
}

.sendafirmas-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.sendafirmas-badge-success {
    background: #d4edda;
    color: #155724;
}

.sendafirmas-badge-warning {
    background: #fff3cd;
    color: #856404;
}

.sendafirmas-btn {
    display: inline-block;
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
}

.sendafirmas-btn-primary {
    background: #007bff;
    color: white;
}

.sendafirmas-btn-primary:hover {
    background: #0056b3;
}

.sendafirmas-btn-secondary {
    background: #6c757d;
    color: white;
}

.sendafirmas-btn-secondary:hover {
    background: #545b62;
}

.sendafirmas-alert {
    padding: 12px 16px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.sendafirmas-alert-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.sendafirmas-alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.sendafirmas-alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.sendafirmas-alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

/* Modal styles */
.sendafirmas-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
}

.sendafirmas-modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.sendafirmas-modal-dialog {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.sendafirmas-modal-header {
    padding: 20px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sendafirmas-modal-title {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.sendafirmas-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6c757d;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sendafirmas-modal-close:hover {
    color: #000;
}

.sendafirmas-modal-body {
    padding: 20px;
}

.sendafirmas-modal-footer {
    padding: 20px;
    border-top: 1px solid #dee2e6;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

/* Signature pad styles */
.sendafirmas-signature-container {
    margin: 20px 0;
    text-align: center;
}

.sendafirmas-signature-pad {
    width: 100%;
    height: 300px;
    border: 2px solid #007bff;
    border-radius: 8px;
    background: rgb(40, 40, 40); /* Dark gray background */
    position: relative;
    cursor: crosshair;
    touch-action: none;
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
}

.sendafirmas-signature-canvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 6px;
}

.sendafirmas-signature-placeholder {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #ffffff; /* White color for visibility on dark background */
    font-size: 16px;
    pointer-events: none;
    font-weight: 500;
}

/* Responsive design */
@media (max-width: 768px) {
    .sendafirmas-container {
        padding: 10px;
    }
    
    .sendafirmas-modal-dialog {
        width: 95%;
        margin: 10px;
    }
    
    .sendafirmas-signature-pad {
        height: 250px;
    }
    
    .sendafirmas-table {
        font-size: 14px;
    }
    
    .sendafirmas-table th,
    .sendafirmas-table td {
        padding: 8px;
    }
    
    .sendafirmas-modal-footer {
        flex-direction: column;
    }
    
    .sendafirmas-btn {
        width: 100%;
        margin-bottom: 10px;
    }
}

@media (max-width: 480px) {
    .sendafirmas-signature-pad {
        height: 200px;
    }
    
    .sendafirmas-modal-header,
    .sendafirmas-modal-body,
    .sendafirmas-modal-footer {
        padding: 15px;
    }
}
</style>

<div class="sendafirmas-container">
    <!-- Replaced Bootstrap breadcrumb with custom HTML -->
    <nav aria-label="breadcrumb">
        <ol class="sendafirmas-breadcrumb">
            <li><a href="?"><?php echo get_string('pluginname', 'local_sendafirmas'); ?></a></li>
            <li><?php echo format_string($course->fullname); ?></li>
        </ol>
    </nav>
    
    <h2><?php echo get_string('pluginname', 'local_sendafirmas'); ?></h2>
    
    <?php if (empty($groups)): ?>
        <div class="sendafirmas-alert sendafirmas-alert-warning">
            <?php echo get_string('nogroups', 'local_sendafirmas'); ?>
        </div>
    <?php else: ?>
        <!-- Replaced Bootstrap form controls with custom styled elements -->
        <div class="sendafirmas-form-group">
            <label for="group-select" class="sendafirmas-label"><?php echo get_string('selectgroup', 'local_sendafirmas'); ?>:</label>
            <select id="group-select" class="sendafirmas-select" onchange="changeGroup()">
                <option value=""><?php echo get_string('selectgroup', 'local_sendafirmas'); ?></option>
                <?php foreach ($groups as $group): ?>
                    <option value="<?php echo $group->id; ?>" <?php echo ($groupid == $group->id) ? 'selected' : ''; ?>>
                        <?php echo format_string($group->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($groupid && !empty($members)): ?>
            <div class="sendafirmas-form-group">
                <label for="search-input" class="sendafirmas-label"><?php echo get_string('search', 'local_sendafirmas'); ?>:</label>
                <input type="text" id="search-input" class="sendafirmas-input" placeholder="<?php echo get_string('search', 'local_sendafirmas'); ?>" oninput="filterMembers()">
            </div>

            <!-- Replaced Bootstrap table with custom styled table -->
            <div style="overflow-x: auto;">
                <table class="sendafirmas-table" id="members-table">
                    <thead>
                        <tr>
                            <th><?php echo get_string('fullname', 'local_sendafirmas'); ?></th>
                            <th><?php echo get_string('status', 'local_sendafirmas'); ?></th>
                            <th><?php echo get_string('actions', 'local_sendafirmas'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr class="member-row" data-name="<?php echo strtolower($member->firstname . ' ' . $member->lastname); ?>">
                                <td><?php echo fullname($member); ?></td>
                                <td>
                                    <span class="sendafirmas-badge <?php echo $member->has_signature ? 'sendafirmas-badge-success' : 'sendafirmas-badge-warning'; ?>">
                                        <?php echo $member->has_signature ? get_string('signed', 'local_sendafirmas') : get_string('pending', 'local_sendafirmas'); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="sendafirmas-btn sendafirmas-btn-primary" onclick="openSignatureModal(<?php echo $member->id; ?>, '<?php echo addslashes(fullname($member)); ?>')">
                                        <?php echo get_string('sign', 'local_sendafirmas'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($groupid): ?>
            <div class="sendafirmas-alert sendafirmas-alert-info">
                <?php echo get_string('nomembers', 'local_sendafirmas'); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Completely rewritten modal with vanilla HTML and CSS -->
<div class="sendafirmas-modal" id="signatureModal" role="dialog" aria-labelledby="signatureModalLabel" aria-hidden="true">
    <div class="sendafirmas-modal-dialog" role="document">
        <div class="sendafirmas-modal-header">
            <h5 class="sendafirmas-modal-title" id="signatureModalLabel"><?php echo get_string('signature_modal_title', 'local_sendafirmas'); ?></h5>
            <button type="button" class="sendafirmas-modal-close" onclick="closeSignatureModal()" aria-label="<?php echo get_string('cancel', 'local_sendafirmas'); ?>">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="sendafirmas-modal-body">
            <p id="student-name" style="font-weight: 600; margin-bottom: 15px;"></p>
            <p><?php echo get_string('draw_signature', 'local_sendafirmas'); ?></p>
            
            <!-- Simplified signature pad with better touch support -->
            <div class="sendafirmas-signature-container">
                <div id="signature-pad" class="sendafirmas-signature-pad" aria-label="<?php echo get_string('signature_canvas', 'local_sendafirmas'); ?>">
                    <canvas id="signature-canvas" class="sendafirmas-signature-canvas"></canvas>
                    <div id="signature-placeholder" class="sendafirmas-signature-placeholder">
                        <?php echo get_string('sign_here', 'local_sendafirmas'); ?>
                    </div>
                </div>
            </div>
            
            <div class="sendafirmas-alert sendafirmas-alert-info" id="signature-info">
                <strong><?php echo get_string('instructions', 'local_sendafirmas'); ?>:</strong><br>
                • <?php echo get_string('draw_with_mouse', 'local_sendafirmas'); ?><br>
                • <?php echo get_string('touch_to_draw', 'local_sendafirmas'); ?><br>
                • <?php echo get_string('use_clear_button', 'local_sendafirmas'); ?>
            </div>
            <div class="sendafirmas-alert sendafirmas-alert-danger" id="signature-error" style="display: none;"></div>
            <div class="sendafirmas-alert sendafirmas-alert-success" id="signature-success" style="display: none;"></div>
        </div>
        <div class="sendafirmas-modal-footer">
            <button type="button" class="sendafirmas-btn sendafirmas-btn-secondary" onclick="clearSignature()">
                <?php echo get_string('clear', 'local_sendafirmas'); ?>
            </button>
            <button type="button" class="sendafirmas-btn sendafirmas-btn-secondary" onclick="closeSignatureModal()">
                <?php echo get_string('cancel', 'local_sendafirmas'); ?>
            </button>
            <button type="button" class="sendafirmas-btn sendafirmas-btn-primary" onclick="saveSignature()" id="save-signature-btn">
                <?php echo get_string('save', 'local_sendafirmas'); ?>
            </button>
        </div>
    </div>
</div>

<script>
console.log('[v0] Initializing vanilla JavaScript signature pad');

// Global variables
let isDrawing = false;
let lastX = 0;
let lastY = 0;
let canvas = null;
let ctx = null;
let currentUserId;
let hasSignature = false;
const courseid = <?php echo $courseid; ?>;
const groupid = <?php echo $groupid; ?>;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('[v0] DOM loaded - vanilla JS version');
});

function initializeSignaturePad() {
    console.log('[v0] Setting up vanilla canvas signature pad');
    
    canvas = document.getElementById('signature-canvas');
    const signatureDiv = document.getElementById('signature-pad');
    const placeholder = document.getElementById('signature-placeholder');
    
    if (!canvas || !signatureDiv) {
        console.error('[v0] Canvas elements not found');
        showError('<?php echo get_string('signature_pad_error', 'local_sendafirmas'); ?>');
        return false;
    }
    
    // Set canvas size to match container
    const rect = signatureDiv.getBoundingClientRect();
    canvas.width = rect.width - 4; // Account for border
    canvas.height = rect.height - 4;
    
    ctx = canvas.getContext('2d');
    if (!ctx) {
        console.error('[v0] Could not get canvas context');
        showError('<?php echo get_string('signature_pad_error', 'local_sendafirmas'); ?>');
        return false;
    }
    
    // Configure drawing style
    ctx.strokeStyle = '#000000'; // Changed to white for visibility on dark background
    ctx.lineWidth = 3;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    
    ctx.fillStyle = 'rgb(255, 255, 255)'; // Dark gray background
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Remove all existing event listeners
    canvas.onmousedown = null;
    canvas.onmousemove = null;
    canvas.onmouseup = null;
    canvas.onmouseleave = null;
    canvas.ontouchstart = null;
    canvas.ontouchmove = null;
    canvas.ontouchend = null;
    canvas.ontouchcancel = null;
    
    // Mouse events
    canvas.addEventListener('mousedown', handleStart, { passive: false });
    canvas.addEventListener('mousemove', handleMove, { passive: false });
    canvas.addEventListener('mouseup', handleEnd, { passive: false });
    canvas.addEventListener('mouseleave', handleEnd, { passive: false });
    
    // Touch events
    canvas.addEventListener('touchstart', handleStart, { passive: false });
    canvas.addEventListener('touchmove', handleMove, { passive: false });
    canvas.addEventListener('touchend', handleEnd, { passive: false });
    canvas.addEventListener('touchcancel', handleEnd, { passive: false });
    
    // Prevent scrolling when touching the canvas
    canvas.addEventListener('touchstart', function(e) {
        e.preventDefault();
    }, { passive: false });
    
    canvas.addEventListener('touchmove', function(e) {
        e.preventDefault();
    }, { passive: false });
    
    console.log('[v0] Canvas signature pad initialized successfully');
    hideError();
    showSuccess('<?php echo get_string('signature_ready', 'local_sendafirmas'); ?>');
    return true;
}

function getEventPos(e) {
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;
    
    let clientX, clientY;
    
    if (e.touches && e.touches.length > 0) {
        clientX = e.touches[0].clientX;
        clientY = e.touches[0].clientY;
    } else {
        clientX = e.clientX;
        clientY = e.clientY;
    }
    
    return {
        x: (clientX - rect.left) * scaleX,
        y: (clientY - rect.top) * scaleY
    };
}

function handleStart(e) {
    e.preventDefault();
    console.log('[v0] Starting to draw');
    
    isDrawing = true;
    const pos = getEventPos(e);
    lastX = pos.x;
    lastY = pos.y;
    
    // Hide placeholder on first draw
    const placeholder = document.getElementById('signature-placeholder');
    if (placeholder) {
        placeholder.style.display = 'none';
    }
    
    // Begin path
    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    
    hasSignature = true;
    hideError();
}

function handleMove(e) {
    if (!isDrawing) return;
    e.preventDefault();
    
    const pos = getEventPos(e);
    
    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
    
    lastX = pos.x;
    lastY = pos.y;
}

function handleEnd(e) {
    if (!isDrawing) return;
    e.preventDefault();
    
    console.log('[v0] Stopped drawing');
    isDrawing = false;
    ctx.beginPath();
}

function showError(message) {
    const errorDiv = document.getElementById('signature-error');
    const successDiv = document.getElementById('signature-success');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }
    if (successDiv) {
        successDiv.style.display = 'none';
    }
}

function showSuccess(message) {
    const errorDiv = document.getElementById('signature-error');
    const successDiv = document.getElementById('signature-success');
    if (successDiv) {
        successDiv.textContent = message;
        successDiv.style.display = 'block';
    }
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
    setTimeout(() => {
        if (successDiv) {
            successDiv.style.display = 'none';
        }
    }, 3000);
}

function hideError() {
    const errorDiv = document.getElementById('signature-error');
    const successDiv = document.getElementById('signature-success');
    if (errorDiv) errorDiv.style.display = 'none';
    if (successDiv) successDiv.style.display = 'none';
}

function changeGroup() {
    const select = document.getElementById('group-select');
    const selectedGroupId = select.value;
    if (selectedGroupId) {
        window.location.href = '?courseid=' + courseid + '&groupid=' + selectedGroupId;
    } else {
        window.location.href = '?courseid=' + courseid;
    }
}

function filterMembers() {
    const searchInput = document.getElementById('search-input');
    const searchTerm = searchInput.value.toLowerCase();
    const rows = document.querySelectorAll('.member-row');
    
    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        if (name.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function openSignatureModal(userid, studentName) {
    console.log('[v0] Opening signature modal for user:', userid, studentName);
    currentUserId = userid;
    hasSignature = false;
    document.getElementById('student-name').textContent = '<?php echo get_string('student', 'local_sendafirmas'); ?>: ' + studentName;
    
    const modal = document.getElementById('signatureModal');
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    
    // Initialize signature pad after modal is shown
    setTimeout(function() {
        initializeSignaturePad();
    }, 200);
}

function closeSignatureModal() {
    console.log('[v0] Closing signature modal');
    const modal = document.getElementById('signatureModal');
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    clearSignature();
    hideError();
}

function clearSignature() {
    console.log('[v0] Clearing signature');
    hasSignature = false;
    
    if (ctx && canvas) {
        ctx.fillStyle = 'rgb(40, 40, 40)'; // Dark gray background
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    }
    
    // Show placeholder again
    const placeholder = document.getElementById('signature-placeholder');
    if (placeholder) {
        placeholder.style.display = 'block';
    }
    
    hideError();
    showSuccess('<?php echo get_string('signature_cleared', 'local_sendafirmas'); ?>');
}

function saveSignature() {
    console.log('[v0] Attempting to save signature');
    
    if (!hasSignature || !canvas) {
        console.log('[v0] No signature data');
        showError('<?php echo get_string('signature_required', 'local_sendafirmas'); ?>');
        return;
    }

    try {
        const imageData = canvas.toDataURL('image/jpeg', 0.95);
        console.log('[v0] JPEG image data generated directly, length:', imageData.length);
        
        // Show loading state
        const saveBtn = document.getElementById('save-signature-btn');
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<?php echo get_string('saving', 'local_sendafirmas'); ?>...';
        saveBtn.disabled = true;
        hideError();

        const requestData = {
            sesskey: M.cfg.sesskey,
            courseid: courseid,
            groupid: groupid,
            userid: currentUserId,
            imageData: imageData
        };
        
        console.log('[v0] Sending JPEG request to save.php');

        fetch('save.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => {
            console.log('[v0] Response received:', response.status, response.statusText);
            return response.json();
        })
        .then(data => {
            console.log('[v0] Response data:', data);
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
            
            if (data.success) {
                showSuccess('<?php echo get_string('saveok', 'local_sendafirmas'); ?>');
                setTimeout(() => {
                    closeSignatureModal();
                    location.reload();
                }, 1500);
            } else {
                showError('<?php echo get_string('saveerror', 'local_sendafirmas'); ?>: ' + (data.error || '<?php echo get_string('unknown_error', 'local_sendafirmas'); ?>'));
            }
        })
        .catch(error => {
            console.error('[v0] Fetch error:', error);
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
            showError('<?php echo get_string('saveerror', 'local_sendafirmas'); ?>: ' + error.message);
        });
        
    } catch (error) {
        console.error('[v0] Error generating signature data:', error);
        showError('<?php echo get_string('signature_generation_error', 'local_sendafirmas'); ?>: ' + error.message);
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('signatureModal');
    if (e.target === modal) {
        closeSignatureModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('signatureModal');
        if (modal.classList.contains('show')) {
            closeSignatureModal();
        }
    }
});
</script>

<?php
echo $OUTPUT->footer();
?>
