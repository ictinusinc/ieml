		</div>
		
        <!-- Javascript Files -->
        <!-- native.history-1.7.1.js -->
        <script type="text/javascript" src="<?php echo WEBAPPROOT.'/includes/js/libs/native.history-1.8b2.min.js'; ?>"></script>
        <!-- jquery-1.9.1.min.js -->
        <script type="text/javascript" src="<?php echo WEBAPPROOT.'/includes/js/libs/jquery-1.9.1.min.js'; ?>"></script>
        <!-- jquery.form-3.34.js -->
        <script type="text/javascript" src="<?php echo WEBAPPROOT.'/includes/js/libs/jquery.form-3.34.0.js'; ?>"></script>
        <!-- bootstrap-2.3.1.min.js -->
        <script type="text/javascript" src="<?php echo WEBAPPROOT.'/includes/js/libs/bootstrap-2.3.2.min.js'; ?>"></script>
        <!-- functions.js -->
        <script type="text/javascript" src="<?php echo WEBAPPROOT.'/includes/js/functions.js'; ?>"></script>
        <!-- UI_lang and _SESSION var dump -->
        <script type="text/javascript">
<?php
        	echo 'var UI_lang = '.json_encode($UI_lang).';';
        	echo 'var _SESSION = '.json_encode($_SESSION).';'; ?>
        </script>
        <script type="text/javascript" src="<?php echo WEBAPPROOT.'/includes/js/table_render.js'; ?>"></script>
        <!-- main.js -->
        <script type="text/javascript" src="<?php echo WEBAPPROOT.'/includes/js/main.js'; ?>"></script>
	</body>
</html>