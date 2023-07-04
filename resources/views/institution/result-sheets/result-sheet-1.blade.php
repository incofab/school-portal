<?php
use App\Actions\ResultSheetUtil; ?>
@extends('institution.result-sheets.result-layout')
@section('content')
  <div style="min-height: 1170px;">
      <div style="width: 900px; margin-left: auto; margin-right: auto; padding-left: 10px; padding-right: 10px;">
          <div class="horizontal-flex" style="background: #FAFAFA; padding-left: 8px; padding-right: 8px; gap: 10px;">
            <div class="avartar">
                <img
                  alt="Institution logo"
                  src={{$institution->photo ?? asset('img/school-logo.png')}}
                />
            </div>
            <div style="align-self: stretch; text-align: center; white-space: nowrap; flex-grow: 2">
              <div style="font-size: 20px;">
                {{$institution->name}}
              </div>
              <div style="font-size: 14px; margin-top: 10px; margin-bottom: 10px;">
                {{$institution->address}}
                <br /> {{$institution->email}}
              </div>
              <div>
                {{$academicSession?->title}} - {{ucfirst($termResult->term->value)}} Term
                Result
              </div>
            </div>
            <div class="avartar">
                <img alt="Student logo" src={{$student->user?->photo_url}} />
              </div>
          </div>
          <div class="horizontal-flex" style="justify-content: space-between; margin-top: 15px;">
            <div><span>Name of Student: </span><span>{{$student?->user?->full_name}}</span></div>
            <div><span>Gender: </span><span>{{$student?->user?->gender}}</span></div>
          </div>
          <div class="horizontal-flex" style="margin-top: 8px; justify-content: space-between;">
            <div><span>Class: </span><span>{{$classification->title}}</span></div>
            <div><span>Position: </span><span>{{$termResult->position . ResultSheetUtil::getPositionSuffix($termResult->position)}}</span></div>
            <div><span>Out of: </span><span>{{$classResultInfo->num_of_students}}</span></div>
          </div>
          <div class="table-container" style=" margin-top: 10px;">
            <table class="result-table" style="width: 100%">
              <thead>
                <tr>
                  <th>Subjects</th>
                  <th>
                    <div class="vertical-header">Assessment 1</div>
                  </th>
                  <th>
                    <div class="vertical-header">Assessment 2</div>
                  </th>
                  <th>
                    <div class="vertical-header">Exam</div>
                  </th>
                  <th>
                    <div class="vertical-header">Total</div>
                  </th>
                  <th>
                    <div class="vertical-header">Grade</div>
                  </th>
                  <th>
                    <div class="vertical-header">Position</div>
                  </th>
                  <th>
                    <div class="vertical-header">Class Average</div>
                  </th>
                  <th>Remark</th>
                </tr>
              </thead>
              <tbody>
                  @foreach ($courseResults as $courseResult)
                      <tr>
                          <td>{{$courseResult->course?->title}}</td>
                          <td>{{$courseResult->first_assessment}}</td>
                          <td>{{$courseResult->second_assessment}}</td>
                          <td>{{$courseResult->exam}}</td>
                          <td>{{$courseResult->result}}</td>
                          <td>{{$courseResult->grade}}</td>
                          <td>{{$courseResult->position}}</td>
                          <td>
                              {{$courseResultInfoData[$courseResult->course_id]?->average}}
                          </td>
                          <td>{{ResultSheetUtil::getRemark($courseResult->grade)}}</td>
                      </tr>
                  @endforeach
              </tbody>
            </table>
          </div>
          <br>
          <div>
            <table class="result-analysis-table">
              <tbody>
                  @foreach ($resultDetails as $resultDetail)
                  <tr>
                    <td style="width: 250px">{{$resultDetail['label']}}</td>
                    <td>{{$resultDetail['value']}}</td>
                  </tr>
                  @endforeach
              </tbody>
            </table>
          </div>
      </div>
    </div>
  @endsection