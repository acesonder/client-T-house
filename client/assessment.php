<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

$auth = new Auth();
$auth->requireLogin();

if (!$auth->hasRole('client')) {
    header('Location: /public/unauthorized.php');
    exit;
}

$user = $auth->getCurrentUser();
$db = Database::getInstance();

// Get existing assessment if any
$existingAssessment = $db->fetchOne(
    "SELECT * FROM intake_assessments WHERE client_id = ? ORDER BY assessment_date DESC LIMIT 1",
    [$user['user_id']]
);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Calculate risk level and priority score based on responses
    $riskLevel = 'medium';
    $priorityScore = 50;
    
    // Determine risk level
    if ($_POST['physical_health_status'] === 'critical' || $_POST['mental_health_status'] === 'critical') {
        $riskLevel = 'critical';
        $priorityScore = 90;
    } elseif ($_POST['substance_use'] === '1' || $_POST['has_children'] === '1') {
        $riskLevel = 'high';
        $priorityScore = 75;
    }
    
    // Recommend services based on needs
    $recommendedServices = [];
    if ($_POST['has_id'] === '0') $recommendedServices[] = 'id_services';
    if ($_POST['substance_use'] === '1') {
        $recommendedServices[] = 'detox';
        $recommendedServices[] = 'rehab';
    }
    if ($_POST['mental_health_status'] !== 'good' && $_POST['mental_health_status'] !== 'excellent') {
        $recommendedServices[] = 'mental_health';
    }
    if ($_POST['has_children'] === '1') $recommendedServices[] = 'children_services';
    if (empty($_POST['employment_status']) || $_POST['employment_status'] === 'unemployed') {
        $recommendedServices[] = 'employment';
    }
    
    $sql = "INSERT INTO intake_assessments (
        client_id, current_living_situation, time_homeless, previous_address,
        physical_health_status, mental_health_status, substance_use, substance_details,
        medications, allergies, has_children, children_details, family_contact,
        emergency_contact, has_id, id_type, legal_issues, employment_status,
        education_level, work_history, income_sources, monthly_income, debts,
        immediate_needs, short_term_goals, long_term_goals, risk_level,
        priority_score, recommended_services, notes, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $result = $db->query($sql, [
        $user['user_id'],
        $_POST['current_living_situation'],
        $_POST['time_homeless'],
        $_POST['previous_address'],
        $_POST['physical_health_status'],
        $_POST['mental_health_status'],
        $_POST['substance_use'],
        $_POST['substance_details'],
        $_POST['medications'],
        $_POST['allergies'],
        $_POST['has_children'],
        $_POST['children_details'],
        $_POST['family_contact'],
        json_encode(['name' => $_POST['emergency_name'], 'phone' => $_POST['emergency_phone']]),
        $_POST['has_id'],
        $_POST['id_type'],
        $_POST['legal_issues'],
        $_POST['employment_status'],
        $_POST['education_level'],
        $_POST['work_history'],
        $_POST['income_sources'],
        $_POST['monthly_income'],
        $_POST['debts'],
        $_POST['immediate_needs'],
        $_POST['short_term_goals'],
        $_POST['long_term_goals'],
        $riskLevel,
        $priorityScore,
        json_encode($recommendedServices),
        $_POST['notes'],
        'pending'
    ]);
    
    if ($result) {
        header('Location: /client/assessment.php?success=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intake Assessment - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="sidebar">
        <div class="navbar-brand" style="color: white;">🏠 Client Portal</div>
        <ul class="sidebar-menu">
            <li><a href="/client/dashboard.php">📊 Dashboard</a></li>
            <li><a href="/client/messages.php">💬 Messages</a></li>
            <li><a href="/client/resources.php">📚 Resources</a></li>
            <li><a href="/client/documents.php">📄 Documents</a></li>
            <li><a href="/client/assessment.php" class="active">📋 Assessments</a></li>
            <li><a href="/client/profile.php">⚙️ Profile Settings</a></li>
            <li><a href="/public/logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="d-flex justify-between align-center mb-3">
            <div>
                <h1>Intake Assessment</h1>
                <p class="text-muted">Help us understand your needs to provide better support</p>
            </div>
            <button class="btn btn-primary navbar-toggle" onclick="UI.toggleSidebar()">☰ Menu</button>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            Assessment submitted successfully! Our team will review it and contact you soon.
        </div>
        <?php endif; ?>

        <?php if ($existingAssessment): ?>
        <div class="alert alert-info">
            You completed an assessment on <?php echo date('F j, Y', strtotime($existingAssessment['assessment_date'])); ?>.
            Status: <strong><?php echo ucfirst($existingAssessment['status']); ?></strong>
            <?php if ($existingAssessment['status'] !== 'completed'): ?>
            <br>You can submit a new assessment below to update your information.
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="assessment-form">
            <!-- Basic Information -->
            <div class="card mb-3">
                <div class="card-header">Basic Information</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Current Living Situation *</label>
                                <select name="current_living_situation" class="form-control" required>
                                    <option value="">Select...</option>
                                    <option value="shelter">Emergency Shelter</option>
                                    <option value="street">Street/Outdoors</option>
                                    <option value="couch_surfing">Couch Surfing</option>
                                    <option value="vehicle">Vehicle</option>
                                    <option value="transitional">Transitional Housing</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">How long have you been homeless? *</label>
                                <input type="text" name="time_homeless" class="form-control" 
                                       placeholder="e.g., 3 months" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Previous Address</label>
                        <textarea name="previous_address" class="form-control" 
                                  placeholder="Your last permanent address"></textarea>
                    </div>
                </div>
            </div>

            <!-- Health Information -->
            <div class="card mb-3">
                <div class="card-header">Health Information</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Physical Health Status *</label>
                                <select name="physical_health_status" class="form-control" required>
                                    <option value="excellent">Excellent</option>
                                    <option value="good">Good</option>
                                    <option value="fair" selected>Fair</option>
                                    <option value="poor">Poor</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Mental Health Status *</label>
                                <select name="mental_health_status" class="form-control" required>
                                    <option value="excellent">Excellent</option>
                                    <option value="good">Good</option>
                                    <option value="fair" selected>Fair</option>
                                    <option value="poor">Poor</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Do you use substances? *</label>
                                <select name="substance_use" class="form-control" required>
                                    <option value="0" selected>No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">If yes, please specify</label>
                                <input type="text" name="substance_details" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Current Medications</label>
                        <textarea name="medications" class="form-control" 
                                  placeholder="List any medications you take"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Allergies</label>
                        <input type="text" name="allergies" class="form-control" 
                               placeholder="Any allergies?">
                    </div>
                </div>
            </div>

            <!-- Social Information -->
            <div class="card mb-3">
                <div class="card-header">Social & Family Information</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Do you have children? *</label>
                                <select name="has_children" class="form-control" required>
                                    <option value="0" selected>No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">If yes, details (ages, custody status)</label>
                                <input type="text" name="children_details" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Family Contact Information</label>
                        <textarea name="family_contact" class="form-control" 
                                  placeholder="Family members we can contact"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Emergency Contact Name</label>
                                <input type="text" name="emergency_name" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Emergency Contact Phone</label>
                                <input type="tel" name="emergency_phone" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Legal & ID -->
            <div class="card mb-3">
                <div class="card-header">Legal & Identification</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Do you have valid ID? *</label>
                                <select name="has_id" class="form-control" required>
                                    <option value="0">No</option>
                                    <option value="1" selected>Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">If yes, what type?</label>
                                <input type="text" name="id_type" class="form-control" 
                                       placeholder="e.g., Driver's License, Passport">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Legal Issues (if any)</label>
                        <textarea name="legal_issues" class="form-control" 
                                  placeholder="Outstanding warrants, court dates, etc."></textarea>
                    </div>
                </div>
            </div>

            <!-- Employment & Education -->
            <div class="card mb-3">
                <div class="card-header">Employment & Education</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Employment Status *</label>
                                <select name="employment_status" class="form-control" required>
                                    <option value="">Select...</option>
                                    <option value="employed_full">Employed Full-Time</option>
                                    <option value="employed_part">Employed Part-Time</option>
                                    <option value="unemployed">Unemployed</option>
                                    <option value="disabled">Unable to Work (Disability)</option>
                                    <option value="student">Student</option>
                                    <option value="retired">Retired</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Education Level</label>
                                <select name="education_level" class="form-control">
                                    <option value="">Select...</option>
                                    <option value="less_than_hs">Less than High School</option>
                                    <option value="hs_diploma">High School Diploma/GED</option>
                                    <option value="some_college">Some College</option>
                                    <option value="associates">Associate's Degree</option>
                                    <option value="bachelors">Bachelor's Degree</option>
                                    <option value="graduate">Graduate Degree</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Work History</label>
                        <textarea name="work_history" class="form-control" 
                                  placeholder="Brief description of your work experience"></textarea>
                    </div>
                </div>
            </div>

            <!-- Financial Information -->
            <div class="card mb-3">
                <div class="card-header">Financial Information</div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Income Sources</label>
                        <input type="text" name="income_sources" class="form-control" 
                               placeholder="e.g., Employment, Benefits, None">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Monthly Income</label>
                                <input type="number" name="monthly_income" class="form-control" 
                                       step="0.01" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Outstanding Debts</label>
                                <input type="text" name="debts" class="form-control" 
                                       placeholder="Brief description">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Goals & Needs -->
            <div class="card mb-3">
                <div class="card-header">Your Goals & Needs</div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Immediate Needs *</label>
                        <textarea name="immediate_needs" class="form-control" required
                                  placeholder="What do you need help with right now?"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Short-Term Goals (Next 3-6 months)</label>
                        <textarea name="short_term_goals" class="form-control"
                                  placeholder="What would you like to achieve in the next few months?"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Long-Term Goals (1+ years)</label>
                        <textarea name="long_term_goals" class="form-control"
                                  placeholder="What are your long-term goals?"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Additional Notes</label>
                        <textarea name="notes" class="form-control"
                                  placeholder="Anything else you'd like us to know?"></textarea>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-muted mb-3">
                        All information is confidential and will only be used to help match you with appropriate services.
                    </p>
                    <button type="submit" class="btn btn-primary btn-lg">Submit Assessment</button>
                    <a href="/client/dashboard.php" class="btn btn-outline btn-lg">Cancel</a>
                </div>
            </div>
        </form>
    </div>

    <script src="/assets/js/main.js"></script>
</body>
</html>
