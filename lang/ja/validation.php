<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines（日本語）
    |--------------------------------------------------------------------------
    */

    'accepted'             => ':attributeを承認してください。',
    'active_url'           => ':attributeは、有効なURLではありません。',
    'after'                => ':attributeには、:dateより後の日付を指定してください。',
    'after_or_equal'       => ':attributeには、:date以降の日付を指定してください。',
    'alpha'                => ':attributeはアルファベットのみ使用できます。',
    'alpha_dash'           => ':attributeはアルファベットとダッシュ(-)及び下線(_)が使用できます。',
    'alpha_num'            => ':attributeはアルファベットと数字が使用できます。',
    'array'                => ':attributeは配列を指定してください。',
    'before'               => ':attributeには、:dateより前の日付を指定してください。',
    'before_or_equal'      => ':attributeには、:date以前の日付を指定してください。',
    'between'              => [
        'numeric' => ':attributeは、:min から :max の間で指定してください。',
        'file'    => ':attributeは、:min から :max キロバイトの間で指定してください。',
        'string'  => ':attributeは、:min から :max 文字の間で指定してください。',
        'array'   => ':attributeは、:min から :max 個の間で指定してください。',
    ],
    'boolean'              => ':attributeは、trueかfalseを指定してください。',
    'confirmed'            => ':attributeと確認フィールドが一致していません。',
    'date'                 => ':attributeには、有効な日付を指定してください。',
    'date_format'          => ':attributeは:format形式で指定してください。',
    'different'            => ':attributeと:otherには、異なる値を指定してください。',
    'digits'               => ':attributeは:digits桁で指定してください。',
    'digits_between'       => ':attributeは:min桁から:max桁の間で指定してください。',
    'email'                => ':attributeには、有効なメールアドレスを指定してください。',
    'exists'               => '選択された:attributeは正しくありません。',
    'file'                 => ':attributeにはファイルを指定してください。',
    'filled'               => ':attributeに値を指定してください。',
    'image'                => ':attributeには、画像ファイルを指定してください。',
    'in'                   => '選択された:attributeは正しくありません。',
    'integer'              => ':attributeは整数で指定してください。',
    'ip'                   => ':attributeには、有効なIPアドレスを指定してください。',
    'json'                 => ':attributeには、有効なJSON文字列を指定してください。',
    'max'                  => [
        'numeric' => ':attributeには、:max以下の数字を指定してください。',
        'file'    => ':attributeには、:max キロバイト以下のファイルを指定してください。',
        'string'  => ':attributeは、:max文字以下で指定してください。',
        'array'   => ':attributeは、:max個以下指定してください。',
    ],
    'mimes'                => ':attributeには、:valuesタイプのファイルを指定してください。',
    'min'                  => [
        'numeric' => ':attributeには、:min以上の数字を指定してください。',
        'file'    => ':attributeには、:min キロバイト以上のファイルを指定してください。',
        'string'  => ':attributeは、:min文字以上で指定してください。',
        'array'   => ':attributeは、:min個以上指定してください。',
    ],
    'not_in'               => '選択された:attributeは正しくありません。',
    'numeric'              => ':attributeには、数字を指定してください。',
    'present'              => ':attributeフィールドが存在している必要があります。',
    'regex'                => ':attributeの形式が正しくありません。',
    'required'             => ':attributeは必ず指定してください。',
    'required_if'          => ':otherが:valueの場合、:attributeを指定してください。',
    'required_unless'      => ':otherが:valuesの場合以外、:attributeを指定してください。',
    'required_with'        => ':valuesを指定する場合は、:attributeも指定してください。',
    'required_with_all'    => ':valuesを指定する場合は、:attributeも指定してください。',
    'required_without'     => ':valuesを指定しない場合は、:attributeを指定してください。',
    'required_without_all' => ':valuesのどれも指定しない場合は、:attributeを指定してください。',
    'same'                 => ':attributeと:otherには、同じ値を指定してください。',
    'size'                 => [
        'numeric' => ':attributeには、:sizeを指定してください。',
        'file'    => ':attributeには、:sizeキロバイトのファイルを指定してください。',
        'string'  => ':attributeは、:size文字で指定してください。',
        'array'   => ':attributeは、:size個指定してください。',
    ],
    'string'               => ':attributeは、文字列を指定してください。',
    'timezone'             => ':attributeには、有効なタイムゾーンを指定してください。',
    'unique'               => ':attributeの値は既に使用されています。',
    'url'                  => ':attributeの形式が正しくありません。',

    /*
    |--------------------------------------------------------------------------
    | 個別項目向けのカスタムメッセージ
    |--------------------------------------------------------------------------
    | 「申請時刻を入力してください。」のように、勤怠申請フォーム固有の文言はここで上書きします。
    */

    'custom' => [
        'request_time' => [
            'required_unless' => '申請時刻を入力してください。',
            'date_format'     => '申請時刻の形式が正しくありません。',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 項目名（:attribute）の日本語表示
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'target_date'  => '対象日',
        'request_type' => '申請種別',
        'memo'         => '申請理由・補足事項',
        'request_time' => '申請時刻',
        'attachment'   => '添付ファイル',
        'halfday_type' => '半休区分',
    ],

];