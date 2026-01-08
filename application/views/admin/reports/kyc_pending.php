<!-- KYC Pending Report -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="fas fa-id-card mr-1"></i> KYC Pending Members</h3>
        <div>
            <button onclick="window.print()" class="btn btn-secondary btn-sm">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="kycPendingTable">
                <thead class="thead-dark">
                    <tr>
                        <th>Member Code</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>ID Proof Type</th>
                        <th>ID Number</th>
                        <th>Pending Documents</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report ?? [] as $member): ?>
                    <tr>
                        <td><?= $member->member_code ?></td>
                        <td><?= $member->first_name . ' ' . $member->last_name ?></td>
                        <td><?= $member->phone ?></td>
                        <td><?= $member->email ?? '-' ?></td>
                        <td><?= $member->id_proof_type ? ucwords(str_replace('_', ' ', $member->id_proof_type)) : 'Not Set' ?></td>
                        <td><?= $member->id_proof_number ?? 'Not Set' ?></td>
                        <td class="text-center">
                            <span class="badge badge-warning"><?= $member->pending_documents ?? 0 ?></span>
                        </td>
                        <td>
                            <a href="<?= site_url('admin/members/edit/' . $member->id) ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> Update
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#kycPendingTable').DataTable({
        "pageLength": 25,
        "order": [[0, "asc"]]
    });
});
</script>