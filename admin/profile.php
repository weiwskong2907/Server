<!-- Add this to the profile tabs section -->
<li class="nav-item">
    <a class="nav-link" id="security-tab" data-bs-toggle="tab" href="#security" role="tab">Security</a>
</li>

<!-- Add this to the tab content section -->
<div class="tab-pane fade" id="security" role="tabpanel">
    <h5 class="mb-4">Security Settings</h5>
    
    <div class="card mb-4">
        <div class="card-header">Two-Factor Authentication</div>
        <div class="card-body">
            <p>Enhance your account security by enabling two-factor authentication.</p>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="enable2fa">
                <label class="form-check-label" for="enable2fa">Enable Two-Factor Authentication</label>
            </div>
            <div id="2faSetup" style="display: none;">
                <div class="alert alert-info">
                    <p><strong>Setup Instructions:</strong></p>
                    <ol>
                        <li>Download an authenticator app like Google Authenticator or Authy</li>
                        <li>Scan the QR code below with your app</li>
                        <li>Enter the 6-digit code from your app to verify</li>
                    </ol>
                </div>
                <div class="text-center mb-3">
                    <img src="https://via.placeholder.com/200x200?text=QR+Code" alt="QR Code" class="img-thumbnail">
                </div>
                <div class="mb-3">
                    <label for="verificationCode" class="form-label">Verification Code</label>
                    <input type="text" class="form-control" id="verificationCode" placeholder="Enter 6-digit code">
                </div>
                <button type="button" class="btn btn-primary">Verify & Enable</button>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">Login History</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>IP Address</th>
                            <th>Browser</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo date('Y-m-d H:i:s'); ?></td>
                            <td><?php echo $_SERVER['REMOTE_ADDR']; ?></td>
                            <td><?php echo $_SERVER['HTTP_USER_AGENT']; ?></td>
                            <td><span class="badge bg-success">Success</span></td>
                        </tr>
                        <!-- Add more rows as needed -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add this JavaScript before the closing </body> tag -->
<script>
$(document).ready(function() {
    $('#enable2fa').change(function() {
        if ($(this).is(':checked')) {
            $('#2faSetup').slideDown();
        } else {
            $('#2faSetup').slideUp();
        }
    });
});
</script>