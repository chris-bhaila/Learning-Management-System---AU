{{-- Expects $log. Links to the student's page when the causer still resolves to a
     real User (getStudentWithTeacherPivot() scopes/IDOR-checks on the controller side —
     this link just points there, it doesn't itself decide access); falls back to plain
     bold text if the causer account was deleted. --}}
@if($log->causer)
    <a href="{{ route('teacher.students.show', $log->causer->id) }}"
       class="font-semibold text-primary hover:underline cursor-pointer">{{ $log->causer->name }}</a>
@else
    <strong>A student</strong>
@endif
