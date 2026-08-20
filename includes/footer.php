        </main>
    </div>
</div>

<?php
// The AI Helpdesk modal, its script deps (educore.js, and Bootstrap's
// JS bundle for the user-menu dropdown in header.php), are already
// rendered once by includes/sidebar.php, which header.php includes at
// the top of every page that also loads this footer. A second copy
// used to be duplicated here - same #aiChatMessages/#aiChatInput ids
// rendered twice on one page, and educore.js/bootstrap loaded twice.
?>
</body>
</html>
