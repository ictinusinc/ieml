		</div>
		
        <!-- Javascript Files -->
        <script type="text/javascript" src="/includes/js/libs/native.history.js"></script>
        <script type="text/javascript" src="/includes/js/libs/jquery-1.7.2.min.js"></script>
        <script type="text/javascript" src="/includes/js/libs/jquery.form-3.04.min.js"></script>
        <script type="text/javascript" src="/includes/js/libs/bootstrap.js"></script>
        <script type="text/javascript" src="/includes/js/functions.js"></script>
        <script type="text/javascript">
<?php
        	echo 'var UI_lang = '.json_encode($UI_lang).';';
        	echo 'var _SESSION = '.json_encode($_SESSION).';'; ?>
        </script>
        <script type="text/javascript" src="/includes/js/main.js?_=<?php echo time(); ?>"></script>
	</body>
</html>