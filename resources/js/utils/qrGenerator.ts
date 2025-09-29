import { supabase } from './supabase';

export interface QRStudentData {
  id?: string;
  student_id: string;
  name: string;
  year: string;
  course: string;
  section: string;
  avatar?: string;
}

export const generateStudentQRData = async (studentId: string): Promise<string> => {
  const { data: student, error } = await supabase
    .from('students')
    .select('*')
    .eq('student_id', studentId)
    .single();

  if (error) throw error;

  const qrData: QRStudentData = {
    id: student.id,
    student_id: student.student_id,
    name: student.name,
    year: student.year,
    course: student.course,
    section: student.section,
    avatar: student.avatar
  };

  return JSON.stringify(qrData);
};

export const createStudent = async (studentData: Omit<QRStudentData, 'id'>) => {
  const { data, error } = await supabase
    .from('students')
    .insert([studentData])
    .select()
    .single();

  if (error) throw error;
  return data;
};