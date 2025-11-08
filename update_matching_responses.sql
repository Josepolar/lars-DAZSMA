-- Add student_answer_pair_id column to matching_responses table
-- This stores which pair the student actually matched to (their answer)

ALTER TABLE matching_responses 
ADD COLUMN student_answer_pair_id INT NULL AFTER pair_id,
ADD FOREIGN KEY (student_answer_pair_id) REFERENCES matching_pairs(pair_id) ON DELETE SET NULL;

-- Update the table comment for clarity
ALTER TABLE matching_responses COMMENT = 'Stores individual student responses for matching games. pair_id is the correct answer, student_answer_pair_id is what the student matched.';
