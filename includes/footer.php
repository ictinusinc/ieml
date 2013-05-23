		</div>
		
        <!-- Javascript Files -->
        <!-- native.history-1.7.1.js -->
        <script type="text/javascript"><?php echo file_get_contents(APPROOT.'/includes/js/libs/native.history-1.7.1.min.js'); ?></script>
        <!-- jquery-1.9.1.min.js -->
        <script type="text/javascript"><?php echo file_get_contents(APPROOT.'/includes/js/libs/jquery-1.9.1.min.js'); ?></script>
        <!-- jquery.form-3.34.js -->
        <script type="text/javascript"><?php echo file_get_contents(APPROOT.'/includes/js/libs/jquery.form-3.34.0.js'); ?></script>
        <!-- bootstrap-2.3.1.min.js -->
        <script type="text/javascript"><?php echo file_get_contents(APPROOT.'/includes/js/libs/bootstrap-2.3.2.min.js'); ?></script>
        <!-- functions.js -->
        <script type="text/javascript"><?php echo file_get_contents(APPROOT.'/includes/js/functions.js'); ?></script>
        <!-- UI_lang and _SESSION var dump -->
        <script type="text/javascript">
<?php
        	echo 'var UI_lang = '.json_encode($UI_lang).';';
        	echo 'var _SESSION = '.json_encode($_SESSION).';'; ?>
        </script>
        <!-- main.js -->
        <script type="text/javascript"><?php echo file_get_contents(APPROOT.'/includes/js/main.js'); ?></script>
	</body>
</html>