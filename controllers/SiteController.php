<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

class SiteController extends Controller
{
    // Login Action
    public function actionLogin()
    {
        if (Yii::$app->request->isPost) {
            $email = Yii::$app->request->post('email');
            $password = Yii::$app->request->post('password');
            
            // Verify the user credentials using the verifyUser method
            if ($this->verifyUser($email, $password)) {
                // Store the email in the session
                Yii::$app->session->set('email', $email);
                Yii::$app->session->setFlash('success', 'Login successful!');
                return $this->redirect(['site/dashboard']);
            } else {
                Yii::$app->session->setFlash('error', 'Invalid email or password!');
            }
        }

        return $this->render('login');
    }



    // Method to verify user credentials
    public function verifyUser($email, $password)
    {
        // Path to the users file
        $filePath = Yii::getAlias('@runtime/users.txt');
        
        // Check if the file exists and read the contents
        if (file_exists($filePath)) {
            $users = file($filePath, FILE_IGNORE_NEW_LINES); // Read file as array

            // Loop through users to check if the email matches
            foreach ($users as $user) {
                list($name, $userEmail, $hashedPassword) = explode(',', $user);
                
                // If email matches, verify password
                if ($userEmail === $email && password_verify($password, $hashedPassword)) {
                    return true; // Valid user
                }
            }
        }

        // Return false if no match is found
        return false;
    }



    // Signup Action
    public function actionSignup()
    {
        if (Yii::$app->request->isPost) {
            // Get the signup form data
            $name = Yii::$app->request->post('name');
            $email = Yii::$app->request->post('email');
            $password = Yii::$app->request->post('password');

            // Check if required fields are provided
            if (!empty($name) && !empty($email) && !empty($password)) {
                // Hash the password before saving it
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Prepare user data to save
                $userData = "$name,$email,$hashedPassword\n";

                // Define the path to save user data
                $filePath = Yii::getAlias('@runtime/users.txt');

                // Save user information to the file (append mode)
                file_put_contents($filePath, $userData, FILE_APPEND);

                // Set a session flash message and redirect to login
                Yii::$app->session->setFlash('success', 'Signup successful! Please log in.');
                return $this->redirect(['site/login']);
            } else {
                Yii::$app->session->setFlash('error', 'Please fill out all fields.');
            }
        }

        return $this->render('signup');
    }


    // Dashboard Action
    public function actionDashboard()
    {
        // Get the logged-in user's email from the session
        $email = Yii::$app->session->get('email'); // Assuming the user's email is stored in session
        
        // Sanitize the email to create a valid filename
        $filename = Yii::getAlias('@runtime/transactions_' . preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($email)) . '.txt');
        
        // Initialize an empty array for transactions
        $transactions = [];
        
        // Check if the user's transaction file exists
        if (file_exists($filename)) {
            // Read the user's transactions from their file
            $fileLines = file($filename, FILE_IGNORE_NEW_LINES); // Read file line by line
    
            // Parse each line as CSV data
            foreach ($fileLines as $line) {
                $transaction = str_getcsv($line);  // Parse the CSV line into an array
                if (count($transaction) === 5) {
                    $transactions[] = $transaction;  // Only add valid transactions (5 fields)
                }
            }
        }
    
        // Render the dashboard view, passing the user's transactions
        return $this->render('dashboard', ['transactions' => $transactions]);
    }
    


    public function actionAddTransaction()
{
    if (Yii::$app->request->isPost) {
        $transactionName = Yii::$app->request->post('transaction-name');
        $category = Yii::$app->request->post('category');
        $amount = Yii::$app->request->post('amount');
        $description = Yii::$app->request->post('description');
        
        // Get the logged-in user's email from the session
        $email = Yii::$app->session->get('email'); // Assuming the email is stored in session
        
        // Sanitize email to create a valid filename
        $filename = Yii::getAlias('@runtime/transactions_' . preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($email)) . '.txt');

        // Create a transaction array
        $transaction = [
            'name' => $transactionName,
            'category' => $category,
            'amount' => $amount,
            'description' => $description,
            'date' => date('Y-m-d'),  // Store only the date
        ];

        // Open the user's transaction file in append mode (create if not exists)
        $file = fopen($filename, 'a');
        if ($file) {
            // Ensure data is saved in CSV format (comma-separated values)
            $transactionString = implode(',', $transaction) . PHP_EOL;
            fwrite($file, $transactionString);
            fclose($file);

            Yii::$app->session->setFlash('success', 'Transaction added successfully!');
            return $this->redirect(['site/dashboard']);
        } else {
            Yii::$app->session->setFlash('error', 'Failed to save transaction!');
        }
    }
}



}
