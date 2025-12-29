<?php
file_put_contents('sample.pdf', "%PDF-1.4\n..."); // Mock PDF
file_put_contents('sample_safe.txt', "Just plain text.");
file_put_contents('sample_unsafe.txt', "Text with <script>alert(1)</script>");
echo "Samples created.\n";
