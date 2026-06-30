@extends('layouts.student')

@section('title', $teacher->name . "'s Class")

@section('content')

{{-- ─── Back Link ─── --}}
<div class="mb-6 animate-fade-up">
    <a href="{{ route('student.classes.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-on-surface-variant
              hover:text-primary transition-colors cursor-pointer">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        My Classes
    </a>
</div>

{{-- ─── Teacher Header ─── --}}
<div class="flex items-center gap-4 mb-8 animate-fade-up">
    <div class="w-16 h-16 rounded-full bg-primary-container shrink-0
                flex items-center justify-center overflow-hidden
                border-2 border-outline-variant/30">
        @if($teacher->avatarUrl())
            <img src="{{ $teacher->avatarUrl() }}"
                 alt="{{ $teacher->name }}"
                 class="w-full h-full object-cover">
        @else
            <span class="text-xl font-bold text-on-primary"
                  style="font-family: var(--font-display);">
                {{ strtoupper(substr($teacher->name, 0, 1)) }}
            </span>
        @endif
    </div>

    <div class="min-w-0">
        <h1 class="text-2xl font-bold text-primary leading-tight"
            style="font-family: var(--font-display);">
            {{ $teacher->name }}
        </h1>
        <p class="text-sm text-on-surface-variant mt-0.5">
            {{ $courses->count() }} {{ Str::plural('course', $courses->count()) }} enrolled
        </p>
    </div>
</div>

{{-- ─── Course Grid ─── --}}
@if($courses->isEmpty())

    <x-card class="py-16 flex flex-col items-center gap-3 text-center animate-fade-up">
        <div class="w-14 h-14 rounded-full bg-surface-container flex items-center justify-center animate-float">
            <span class="material-symbols-outlined text-outline text-[28px]">library_books</span>
        </div>
        <p class="text-sm font-semibold text-on-surface">No courses enrolled yet</p>
        <p class="text-xs text-on-surface-variant max-w-xs">
            You're in {{ $teacher->name }}'s class but haven't enrolled in any of their courses yet.
            Use a course token from your teacher to get started.
        </p>
        <x-button type="button" variant="primary" icon="vpn_key"
                  class="mt-2" onclick="openEnrollModal()">
            Enroll with Token
        </x-button>
    </x-card>

@else

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 animate-fade-up">
        @foreach($courses as $course)
            <a href="{{ route('student.courses.show', $course->id) }}"
               class="bg-surface-white border border-outline-variant/40 rounded-[20px] p-5
                      flex flex-col gap-3 min-w-0
                      shadow-[0px_2px_8px_rgba(30,42,74,0.06)]
                      hover:shadow-[0px_8px_24px_rgba(30,42,74,0.12)] hover:-translate-y-0.5
                      transition-all duration-200 group cursor-pointer">

                {{-- Course icon --}}
                <div class="w-10 h-10 rounded-xl bg-gold/20
                            flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-primary text-[20px]">library_books</span>
                </div>

                {{-- Title --}}
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-on-surface text-sm leading-snug
                              group-hover:text-primary transition-colors line-clamp-2">
                        {{ $course->title }}
                    </p>
                </div>

                {{-- Meta row --}}
                <div class="flex items-center justify-between">
                    <span class="text-xs text-outline">
                        {{ $course->units_count }} {{ Str::plural('unit', $course->units_count) }}
                    </span>
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
