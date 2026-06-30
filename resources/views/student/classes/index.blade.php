@extends('layouts.student')

@section('title', 'My Classes')

@section('content')

{{-- ─── Page Header ─── --}}
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-8">
    <div>
        <h1 class="text-2xl font-bold text-primary" style="font-family: var(--font-display);">
            My Classes
        </h1>
        <p class="mt-1 text-sm text-on-surface-variant">
            @if($teachers->isNotEmpty())
                {{ $teachers->count() }} {{ Str::plural('class', $teachers->count()) }} joined
            @else
                Teachers you've joined via class token
            @endif
        </p>
    </div>
    <x-button type="button" variant="primary" icon="vpn_key" onclick="openEnrollModal()">
        Enroll with Token
    </x-button>
</div>

@if($teachers->isEmpty())

    {{-- ─── Empty State ─── --}}
    <x-card class="py-20 flex flex-col items-center gap-3 text-center animate-fade-up">
        <div class="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center animate-float">
            <span class="material-symbols-outlined text-outline text-[30px]">school</span>
        </div>
        <p class="text-sm font-semibold text-on-surface">No classes yet</p>
        <p class="text-xs text-on-surface-variant max-w-xs">
            Ask your teacher for a class token to join their class. Once you're in, their courses will appear here.
        </p>
        <x-button type="button" variant="primary" icon="vpn_key"
                  class="mt-2" onclick="openEnrollModal()">
            Enroll with Token
        </x-button>
    </x-card>

@else

    {{-- ─── Teacher Cards Grid ─── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 animate-fade-up">
        @foreach($teachers as $teacher)
            <a href="{{ route('student.classes.show', $teacher->id) }}"
               class="bg-surface-white border border-outline-variant/40 rounded-[20px] p-5
                      flex flex-col gap-4 min-w-0
                      shadow-[0px_2px_8px_rgba(30,42,74,0.06)]
                      hover:shadow-[0px_8px_24px_rgba(30,42,74,0.12)] hover:-translate-y-0.5
                      transition-all duration-200 group cursor-pointer">

                {{-- Avatar --}}
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-12 h-12 rounded-full bg-primary-container shrink-0
                                flex items-center justify-center overflow-hidden border border-outline-variant/30">
                        @if($teacher->avatarUrl())
                            <img src="{{ $teacher->avatarUrl() }}"
                                 alt="{{ $teacher->name }}"
                                 class="w-full h-full object-cover">
                        @else
                            <span class="text-base font-bold text-on-primary"
                                  style="font-family: var(--font-display);">
                                {{ strtoupper(substr($teacher->name, 0, 1)) }}
                            </span>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-on-surface text-sm leading-snug
                                  group-hover:text-primary transition-colors truncate">
                            {{ $teacher->name }}
                        </p>
                        <p class="text-xs text-on-surface-variant mt-0.5">Teacher</p>
                    </div>
                </div>

                {{-- Course count + arrow --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5 text-xs text-on-surface-variant">
                        <span class="material-symbols-outlined text-[15px]">library_books</span>
                        <span>
                            {{ $teacher->enrolled_course_count }}
                            {{ Str::plural('course', $teacher->enrolled_course_count) }} enrolled
                        </span>
                    </div>
                    <span class="material-symbols-outlined text-[16px] text-outline
                                 group-hover:text-primary transition-colors">
                        arrow_forward
                    </span>
                </div>

            </a>
        @endforeach
    </div>

@endif

@endsection

@include('student._enroll_modal')
