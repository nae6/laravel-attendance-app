@extends('layouts.admin')

@section('title', 'スタッフ一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/history.css') }}">
@endsection

@section('content')
<h1 class="content-title">スタッフ一覧</h1>

<div class="table__wrapper font-setting">
    <table>
        <thead>
            <tr class="table__header">
                <th>名前</th>
                <th>メールアドレス</th>
                <th>月次勤怠</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr class="table__inner">
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td class="detail__link">
                    <a href="{{ route('staff.attendance.list', ['staff' => $user]) }}">詳細</a>
                </td>
            </tr>
            @empty
            <tr class="table__inner">
                <td colspan="3">スタッフの登録がありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection