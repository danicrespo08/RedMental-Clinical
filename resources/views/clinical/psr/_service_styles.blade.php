{{-- Reusable styles for PSR / IT / TCM service dashboards.
     Mirrors albamed/dashboards/partials/service-styles.blade.php. --}}
<style>
    .svc-stat {
        background: white; border-radius: 1rem; padding: 1.25rem 1.5rem;
        border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 1rem;
        transition: all .25s ease;
    }
    .svc-stat:hover { transform: translateY(-2px); box-shadow: 0 8px 25px -6px rgba(0,0,0,.08); }

    .svc-stat-icon {
        width: 48px; height: 48px; border-radius: .85rem;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }

    .svc-card {
        background: white; border-radius: 1rem; border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0,0,0,.03); overflow: hidden;
    }

    .svc-card-header {
        padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between;
    }

    .svc-card-body { padding: 1.25rem 1.5rem; }

    .svc-badge {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .3rem .7rem; border-radius: .5rem;
        font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em;
        white-space: nowrap;
    }

    .svc-table {
        width: 100%; border-collapse: separate; border-spacing: 0;
        min-width: 700px;
    }
    .svc-table th {
        text-align: left; padding: 1rem 1.5rem;
        font-size: .65rem; font-weight: 800; color: #64748b;
        text-transform: uppercase; letter-spacing: .05em;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        white-space: nowrap;
    }
    .svc-table td {
        padding: 1rem 1.5rem; font-size: .85rem; font-weight: 500;
        border-bottom: 1px solid #f1f5f9; color: #334155;
        vertical-align: middle;
    }
    .svc-table tr:hover td { background: #f8fafc; }

    .svc-link {
        display: flex; align-items: center; gap: .75rem;
        padding: .75rem 1rem; border-radius: .75rem;
        text-decoration: none; transition: all .2s;
        border: 1px solid #e2e8f0; background: white;
    }
    .svc-link:hover { transform: translateY(-2px); box-shadow: 0 6px 20px -4px rgba(0,0,0,.06); }

    .svc-link-icon {
        width: 40px; height: 40px; border-radius: .65rem;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }

    /* PSR admission status badges */
    .status-admitted        { background: #dcfce7; color: #166534; }
    .status-discharged      { background: #e2e8f0; color: #475569; }
    .status-on_hold         { background: #ffedd5; color: #9a3412; }
    .status-pending_intake  { background: #fef3c7; color: #92400e; }
    .status-intake_complete { background: #dbeafe; color: #1e40af; }
</style>
