<?php namespace App\Http\Controllers;
use Carbon\Carbon;
use \Request;
use \Input;
use \Validator;
use \Cache;
use \App\QuestionsAbc;
use \App\QuestionsYesNo;

/**
 * Question controller
 *
 * @author: Janusz Smoła
 */

class QuestionController extends Controller {

    /**
     * Abc question list
     */
    public function abc()
    {
        $itemsPerPage = Input::get('ilosc');
        $questions = QuestionsAbc::All();

        return view('abc', array(
            'questions' => $questions
        ));
    }

    /**
     * Yesno question list
     */
    public function yesno()
    {
        $itemsPerPage = Input::get('ilosc');
    }
    
    /**
     * Abc question show form
     */
    public function addAbc()
    {
        return view('addAbcQuestion');
    }

    /**
     * Display question with details
     */
    public function questionDetails()
    {
        if (!Cache::has('questionsYesNoCount')) {
            Cache::add('questionsYesNoCount', QuestionsYesNo::count(), 5);
        }
        if (!Cache::has('questionsAbcCount')) {
            Cache::add('questionsAbcCount', QuestionsAbc::count(), 5);
        }

        $yesnoCount = Cache::get('questionsYesNoCount');
        $abcCount = Cache::get('questionsAbcCount');
        $rand = rand(0, $yesnoCount + $abcCount);
        if(rand(0, $yesnoCount + $abcCount) < $yesnoCount) {
            $question = QuestionsYesNo::orderByRaw("RAND()")->first();
        } else {
            $question = QuestionsAbc::orderByRaw("RAND()")->first();
        }

        return view('questionDetails', [
            'question' => $question
        ]);
    }
    
    /*
     * Add Yes/No question with or without image
     */
    public function addYesNo()
    {
        if(Request::isMethod('post')) {

            $data = [
                'question' => Input::get('question'),
                'accepted' => false,
                'correct_answer' => Input::get('correct_answer'),
                'category' => Input::get('category')
            ];

            if(Request::file('uploaded_picture')) {
                $data['picture'] = Request::file('uploaded_picture');
            }

            $validator = Validator::make($data, QuestionsYesNo::getValidationAddRules());
            $errorMessages = $validator->messages();

            if(Request::hasFile('uploaded_picture') && $validator->passes())
            {
                $extension = Request::file('uploaded_picture')->getClientOriginalExtension();

                $picture = null;

                while(!$picture) {
                    $random_name = str_random(12) . $extension;
                    if(!QuestionsYesNo::where('picture', '=', $random_name)->first()) {
                        $picture = $random_name;
                    }
                }

                $data['picture'] = $picture;

                try {
                    Request::file('uploaded_picture')->move(QuestionsYesNo::getImagePath(), $picture);
                } catch (Exception $e) {
                    $errorMessages->add('picture', 'An error message.');
                }

            }

            if($errorMessages->isEmpty()) {
                $questionRow = new QuestionsYesNo();

                if(!$questionRow->fill($data)->save()) {
                    $errorMessages->add('form', 'An error message.');
                }
            }

            return view('addYesNoQuestion', [
                'messages' => $errorMessages
            ]);
        }

        return view('addYesNoQuestion');
    }
    
    
    /**
     * Add ABC question with or without image
     */
    public function uploadQuestion()
    {
        $question = $_POST['question'];
        $answer_a = $_POST['answer-1'];
        $answer_b = $_POST['answer-2'];
        $answer_c = $_POST['answer-3'];
        $correct_answer = $_POST['correct_answer'];
        $category = $_POST['category'];
        $accepted = '0';
        
        $query = new \App\QuestionsAbc();
        
        if($_FILES['uploadedfile']['size'] > 0)
        {
            $extension = substr($_FILES['uploadedfile']['name'], strrpos($_FILES['uploadedfile']['name'], '.') +1);

            do {
                $randomCode = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 1).substr(md5(time()),1,8);
                $count = \App\QuestionsAbc::where('picture', '=', $randomCode)->count();
            }
            while($count > 1);
            
            $final_name = $randomCode . '.' . $extension;
            
            
            $target = __DIR__ . '/../../../public_html/images/';
            $target_final = $target . basename($final_name);

            if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_final)) {
                
                $query->question = $question;
                $query->answer_a = $answer_a;
                $query->answer_b = $answer_b;
                $query->answer_c = $answer_c;
                $query->accepted = $accepted;
                $query->correct_answer = $correct_answer;
                $query->picture = $final_name;
                $query->category = $category;
            
                if($query->save()) {
    //                echo json_encode();
                } else {
    //                echo json_encode();
                }
            }
        }
        else {
            
            $query->question = $question;
            $query->answer_a = $answer_a;
            $query->answer_b = $answer_b;
            $query->answer_c = $answer_c;
            $query->accepted = $accepted;
            $query->correct_answer = $correct_answer;
            $query->picture = '';
            $query->category = $category;
            if($query->save()) {
//                echo json_encode();
            }
            else {
//                echo json_encode();
            }
        }

        return view('addAbcQuestion');
    }

    
    
}
